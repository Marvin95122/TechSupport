<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\CambioEstado;
use App\Models\Categoria;
use App\Models\Equipo;
use App\Models\EstadoTicket;
use App\Models\Prioridad;
use App\Models\Ticket;
use App\Models\TicketObservacion;
use App\Models\TicketSolucion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Datos ficticios, identificados con el prefijo DEMO, para la demostración
 * funcional de TechSupport. No crea usuarios ni modifica sus contraseñas.
 *
 * Es intencionalmente independiente de DatabaseSeeder para evitar que los
 * registros de demostración se carguen en instalaciones normales.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->protegerEjecucionEnProduccion();

        // Garantiza que existan los catálogos sin duplicar los registros actuales.
        $this->call(CatalogosTicketsSeeder::class);

        $administrador = User::query()
            ->where('role', 'administrador')
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        $tecnico = User::query()
            ->where('role', 'tecnico')
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        $solicitante = User::query()
            ->where('role', 'solicitante')
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if (! $administrador || ! $tecnico || ! $solicitante) {
            throw new RuntimeException(
                'DemoDataSeeder requiere al menos un administrador, un técnico y un solicitante activos. No crea ni modifica cuentas.'
            );
        }

        DB::transaction(function () use ($administrador, $tecnico, $solicitante): void {
            $areas = collect([
                'Dirección General',
                'Recursos Humanos',
                'Servicios Escolares',
                'Laboratorio de Cómputo',
                'Biblioteca',
            ])->mapWithKeys(function (string $nombre): array {
                return [$nombre => Area::query()->firstOrCreate(['nombre' => $nombre])];
            });

            $datosEquipos = [
                [
                    'codigo' => 'DEMO-EQ-001',
                    'area' => 'Laboratorio de Cómputo',
                    'tipo' => 'Computadora de escritorio',
                    'marca' => 'Dell',
                    'modelo' => 'OptiPlex 7090',
                    'serie' => 'DEMO-SER-001',
                    'estado' => 'operativo',
                ],
                [
                    'codigo' => 'DEMO-EQ-002',
                    'area' => 'Dirección General',
                    'tipo' => 'Laptop',
                    'marca' => 'Lenovo',
                    'modelo' => 'ThinkPad E14',
                    'serie' => 'DEMO-SER-002',
                    'estado' => 'operativo',
                ],
                [
                    'codigo' => 'DEMO-EQ-003',
                    'area' => 'Recursos Humanos',
                    'tipo' => 'Impresora láser',
                    'marca' => 'HP',
                    'modelo' => 'LaserJet Pro M404',
                    'serie' => 'DEMO-SER-003',
                    'estado' => 'operativo',
                ],
                [
                    'codigo' => 'DEMO-EQ-004',
                    'area' => 'Servicios Escolares',
                    'tipo' => 'Computadora de escritorio',
                    'marca' => 'HP',
                    'modelo' => 'ProDesk 400 G7',
                    'serie' => 'DEMO-SER-004',
                    'estado' => 'operativo',
                ],
                [
                    'codigo' => 'DEMO-EQ-005',
                    'area' => 'Laboratorio de Cómputo',
                    'tipo' => 'Switch de red',
                    'marca' => 'Cisco',
                    'modelo' => 'Catalyst 2960',
                    'serie' => 'DEMO-SER-005',
                    'estado' => 'operativo',
                ],
                [
                    'codigo' => 'DEMO-EQ-006',
                    'area' => 'Biblioteca',
                    'tipo' => 'Laptop',
                    'marca' => 'Dell',
                    'modelo' => 'Latitude 5420',
                    'serie' => 'DEMO-SER-006',
                    'estado' => 'operativo',
                ],
                [
                    'codigo' => 'DEMO-EQ-007',
                    'area' => 'Servicios Escolares',
                    'tipo' => 'Proyector',
                    'marca' => 'Epson',
                    'modelo' => 'PowerLite X49',
                    'serie' => 'DEMO-SER-007',
                    'estado' => 'mantenimiento',
                ],
                [
                    'codigo' => 'DEMO-EQ-008',
                    'area' => 'Dirección General',
                    'tipo' => 'Escáner',
                    'marca' => 'Canon',
                    'modelo' => 'imageFORMULA R40',
                    'serie' => 'DEMO-SER-008',
                    'estado' => 'operativo',
                ],
            ];

            $equipos = collect($datosEquipos)->mapWithKeys(function (array $datos) use ($areas, $tecnico): array {
                $equipo = Equipo::query()->updateOrCreate(
                    ['codigo' => $datos['codigo']],
                    [
                        'area_id' => $areas->get($datos['area'])->id,
                        'responsable_id' => $tecnico->id,
                        'tipo' => $datos['tipo'],
                        'marca' => $datos['marca'],
                        'modelo' => $datos['modelo'],
                        'serie' => $datos['serie'],
                        'estado' => $datos['estado'],
                        'activo' => true,
                    ]
                );

                return [$datos['codigo'] => $equipo];
            });

            $categorias = Categoria::query()->get()->keyBy('nombre');
            $prioridades = Prioridad::query()->get()->keyBy('nombre');
            $estados = EstadoTicket::query()->get()->keyBy('nombre');

            $datosTickets = $this->obtenerDatosTickets();

            foreach ($datosTickets as $indice => $datos) {
                $fechaCreacion = now()
                    ->startOfDay()
                    ->subDays($datos['dias'])
                    ->addHours(9 + ($indice % 4))
                    ->addMinutes(($indice * 7) % 60);

                $fechaAsignacion = $fechaCreacion->copy()->addHours(2);
                $fechaProceso = $fechaAsignacion->copy()->addHours(2);
                $fechaSolucion = $fechaProceso->copy()->addHour();
                $fechaResolucion = $fechaSolucion->copy()->addHours(3);
                $fechaCierre = $fechaResolucion->copy()->addDay();

                $requiereAsignacion = $datos['estado'] !== 'Pendiente';
                $requiereProceso = in_array($datos['estado'], ['En proceso', 'Resuelto', 'Cerrado'], true);
                $requiereResolucion = in_array($datos['estado'], ['Resuelto', 'Cerrado'], true);
                $requiereCierre = $datos['estado'] === 'Cerrado';

                $ticket = Ticket::query()->firstOrNew(['folio' => $datos['folio']]);
                $ticket->fill([
                    'folio' => $datos['folio'],
                    'solicitante_id' => $solicitante->id,
                    'equipo_id' => $equipos->get($datos['equipo'])->id,
                    'categoria_id' => $categorias->get($datos['categoria'])->id,
                    'prioridad_id' => $prioridades->get($datos['prioridad'])->id,
                    'estado_id' => $estados->get($datos['estado'])->id,
                    'asunto' => $datos['asunto'],
                    'descripcion' => $datos['descripcion'],
                ]);
                $ticket->forceFill([
                    'tecnico_id' => $requiereAsignacion ? $tecnico->id : null,
                    'asignado_por_id' => $requiereAsignacion ? $administrador->id : null,
                    'creado_en' => $fechaCreacion,
                    'asignado_en' => $requiereAsignacion ? $fechaAsignacion : null,
                    'resuelto_en' => $requiereResolucion ? $fechaResolucion : null,
                    'cerrado_en' => $requiereCierre ? $fechaCierre : null,
                ]);
                $ticket->save();

                $transiciones = [];

                if ($requiereAsignacion) {
                    $transiciones[] = [
                        'anterior' => 'Pendiente',
                        'nuevo' => 'Asignado',
                        'usuario' => $administrador,
                        'fecha' => $fechaAsignacion,
                    ];
                }

                if ($requiereProceso) {
                    $transiciones[] = [
                        'anterior' => 'Asignado',
                        'nuevo' => 'En proceso',
                        'usuario' => $tecnico,
                        'fecha' => $fechaProceso,
                    ];
                }

                if ($requiereResolucion) {
                    $transiciones[] = [
                        'anterior' => 'En proceso',
                        'nuevo' => 'Resuelto',
                        'usuario' => $tecnico,
                        'fecha' => $fechaResolucion,
                    ];
                }

                if ($requiereCierre) {
                    $transiciones[] = [
                        'anterior' => 'Resuelto',
                        'nuevo' => 'Cerrado',
                        'usuario' => $tecnico,
                        'fecha' => $fechaCierre,
                    ];
                }

                foreach ($transiciones as $transicion) {
                    CambioEstado::query()->updateOrCreate(
                        [
                            'ticket_id' => $ticket->id,
                            'estado_nuevo_id' => $estados->get($transicion['nuevo'])->id,
                        ],
                        [
                            'usuario_id' => $transicion['usuario']->id,
                            'estado_anterior_id' => $estados->get($transicion['anterior'])->id,
                            'fecha' => $transicion['fecha'],
                        ]
                    );
                }

                if ($datos['observacion'] !== null) {
                    TicketObservacion::query()->updateOrCreate(
                        [
                            'ticket_id' => $ticket->id,
                            'tipo' => $datos['observacion']['tipo'],
                            'contenido' => $datos['observacion']['contenido'],
                        ],
                        [
                            'usuario_id' => $tecnico->id,
                            'fecha' => $fechaAsignacion->copy()->addHour(),
                        ]
                    );
                }

                if ($datos['solucion'] !== null) {
                    TicketSolucion::query()->updateOrCreate(
                        [
                            'ticket_id' => $ticket->id,
                            'diagnostico' => $datos['solucion']['diagnostico'],
                        ],
                        [
                            'usuario_id' => $tecnico->id,
                            'acciones_realizadas' => $datos['solucion']['acciones_realizadas'],
                            'solucion' => $datos['solucion']['solucion'],
                            'fecha' => $fechaSolucion,
                        ]
                    );
                }
            }
        });

        if ($this->command !== null) {
            $this->command->info('Datos de demostración creados o actualizados correctamente.');
            $this->command->line('Áreas de demostración: 5');
            $this->command->line('Equipos de demostración: 8');
            $this->command->line('Tickets de demostración: 12');
            $this->command->line('Cambios de estado: 26');
            $this->command->line('Observaciones y actividades: 8');
            $this->command->line('Diagnósticos y soluciones: 8');
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function obtenerDatosTickets(): array
    {
        return [
            [
                'folio' => 'DEMO-TS-001', 'dias' => 2, 'estado' => 'Pendiente',
                'equipo' => 'DEMO-EQ-001', 'categoria' => 'Hardware', 'prioridad' => 'Alta',
                'asunto' => 'La computadora no enciende',
                'descripcion' => 'El equipo de escritorio no inicia al presionar el botón de encendido. Se requiere revisar la alimentación eléctrica y los componentes internos.',
                'observacion' => null, 'solucion' => null,
            ],
            [
                'folio' => 'DEMO-TS-002', 'dias' => 7, 'estado' => 'Pendiente',
                'equipo' => 'DEMO-EQ-002', 'categoria' => 'Software', 'prioridad' => 'Media',
                'asunto' => 'Instalación de herramienta ofimática',
                'descripcion' => 'Se solicita instalar y configurar la herramienta ofimática institucional en el equipo asignado al área.',
                'observacion' => null, 'solucion' => null,
            ],
            [
                'folio' => 'DEMO-TS-003', 'dias' => 12, 'estado' => 'Asignado',
                'equipo' => 'DEMO-EQ-003', 'categoria' => 'Periféricos', 'prioridad' => 'Media',
                'asunto' => 'La impresora presenta atascos de papel',
                'descripcion' => 'La impresora presenta atascos frecuentes durante los trabajos de impresión. Se requiere inspeccionar el mecanismo de alimentación.',
                'observacion' => ['tipo' => 'observacion', 'contenido' => 'Se recibió el equipo y se programó la revisión física del mecanismo de alimentación.'],
                'solucion' => null,
            ],
            [
                'folio' => 'DEMO-TS-004', 'dias' => 17, 'estado' => 'Asignado',
                'equipo' => 'DEMO-EQ-004', 'categoria' => 'Accesos', 'prioridad' => 'Alta',
                'asunto' => 'Problemas para acceder al sistema institucional',
                'descripcion' => 'El usuario reporta que sus credenciales no permiten ingresar al sistema institucional desde su estación de trabajo.',
                'observacion' => ['tipo' => 'actividad', 'contenido' => 'Se inició la revisión de la cuenta y se verificó la disponibilidad del servicio de autenticación.'],
                'solucion' => null,
            ],
            [
                'folio' => 'DEMO-TS-005', 'dias' => 25, 'estado' => 'En proceso',
                'equipo' => 'DEMO-EQ-005', 'categoria' => 'Red', 'prioridad' => 'Crítica',
                'asunto' => 'Intermitencia en la conexión de red',
                'descripcion' => 'Varios equipos del laboratorio pierden conectividad de manera intermitente. Se solicita revisar el switch y los enlaces de red.',
                'observacion' => ['tipo' => 'observacion', 'contenido' => 'Se detectaron pérdidas intermitentes de comunicación en uno de los enlaces. Continúa el diagnóstico.'],
                'solucion' => [
                    'diagnostico' => 'Se identificó intermitencia en un enlace del switch de distribución.',
                    'acciones_realizadas' => 'Se revisaron los puertos, el cableado y los registros de conexión del equipo de red.',
                    'solucion' => 'Se aplicó un ajuste preliminar al enlace y se mantiene el monitoreo para confirmar la estabilidad.',
                ],
            ],
            [
                'folio' => 'DEMO-TS-006', 'dias' => 35, 'estado' => 'En proceso',
                'equipo' => 'DEMO-EQ-006', 'categoria' => 'Software', 'prioridad' => 'Media',
                'asunto' => 'Actualización pendiente del sistema operativo',
                'descripcion' => 'El equipo presenta actualizaciones pendientes y algunos componentes no se encuentran en su versión recomendada.',
                'observacion' => ['tipo' => 'actividad', 'contenido' => 'Se verificó el espacio disponible y se inició la revisión de compatibilidad de las actualizaciones.'],
                'solucion' => [
                    'diagnostico' => 'Se detectaron actualizaciones acumulativas pendientes y controladores por revisar.',
                    'acciones_realizadas' => 'Se liberó espacio temporal y se preparó la instalación de los componentes compatibles.',
                    'solucion' => 'Se aplicó una actualización preliminar; queda pendiente validar el comportamiento después del reinicio.',
                ],
            ],
            [
                'folio' => 'DEMO-TS-007', 'dias' => 45, 'estado' => 'En proceso',
                'equipo' => 'DEMO-EQ-007', 'categoria' => 'Configuración', 'prioridad' => 'Baja',
                'asunto' => 'Configuración de proyector para aula',
                'descripcion' => 'Se requiere ajustar la resolución y la proyección de imagen para utilizar el equipo en las actividades del área.',
                'observacion' => ['tipo' => 'observacion', 'contenido' => 'Se comprobó la conexión de video y se están realizando pruebas con distintas resoluciones.'],
                'solucion' => [
                    'diagnostico' => 'La resolución de salida del equipo conectado no coincide con la resolución recomendada del proyector.',
                    'acciones_realizadas' => 'Se revisaron las entradas de video y se ajustaron los parámetros de visualización.',
                    'solucion' => 'Se configuró una resolución compatible; falta completar la prueba con el equipo del aula.',
                ],
            ],
            [
                'folio' => 'DEMO-TS-008', 'dias' => 60, 'estado' => 'Resuelto',
                'equipo' => 'DEMO-EQ-008', 'categoria' => 'Periféricos', 'prioridad' => 'Baja',
                'asunto' => 'El escáner no es reconocido por la computadora',
                'descripcion' => 'El escáner no aparece disponible en la estación de trabajo después de conectarlo mediante USB.',
                'observacion' => ['tipo' => 'actividad', 'contenido' => 'Se verificó el cable USB y se reinstaló el controlador correspondiente al modelo.'],
                'solucion' => [
                    'diagnostico' => 'El controlador del escáner estaba dañado y el dispositivo no completaba su inicialización.',
                    'acciones_realizadas' => 'Se retiró el controlador anterior, se instaló la versión compatible y se reinició el equipo.',
                    'solucion' => 'El escáner fue reconocido correctamente y se realizó una prueba de digitalización satisfactoria.',
                ],
            ],
            [
                'folio' => 'DEMO-TS-009', 'dias' => 75, 'estado' => 'Resuelto',
                'equipo' => 'DEMO-EQ-001', 'categoria' => 'Hardware', 'prioridad' => 'Alta',
                'asunto' => 'Rendimiento lento en estación de trabajo',
                'descripcion' => 'La computadora presenta tiempos elevados de inicio y respuesta al abrir las aplicaciones utilizadas por el área.',
                'observacion' => ['tipo' => 'observacion', 'contenido' => 'Se revisó el estado del almacenamiento y se identificaron procesos innecesarios al inicio.'],
                'solucion' => [
                    'diagnostico' => 'Se identificó saturación de procesos de inicio y espacio temporal acumulado.',
                    'acciones_realizadas' => 'Se depuraron archivos temporales, se revisaron aplicaciones de inicio y se verificó el estado del disco.',
                    'solucion' => 'Se optimizó el inicio del sistema y el equipo recuperó un tiempo de respuesta adecuado.',
                ],
            ],
            [
                'folio' => 'DEMO-TS-010', 'dias' => 95, 'estado' => 'Cerrado',
                'equipo' => 'DEMO-EQ-002', 'categoria' => 'Accesos', 'prioridad' => 'Media',
                'asunto' => 'Restablecimiento de acceso a cuenta institucional',
                'descripcion' => 'El solicitante no podía acceder a su cuenta institucional y requirió apoyo para recuperar el acceso.',
                'observacion' => ['tipo' => 'actividad', 'contenido' => 'Se validó la identidad del solicitante y se inició el procedimiento autorizado de recuperación.'],
                'solucion' => [
                    'diagnostico' => 'La cuenta se encontraba bloqueada después de varios intentos fallidos de autenticación.',
                    'acciones_realizadas' => 'Se verificó la cuenta, se restablecieron las credenciales y se comprobó el acceso con el usuario.',
                    'solucion' => 'El solicitante confirmó que pudo ingresar nuevamente al sistema institucional.',
                ],
            ],
            [
                'folio' => 'DEMO-TS-011', 'dias' => 120, 'estado' => 'Cerrado',
                'equipo' => 'DEMO-EQ-005', 'categoria' => 'Red', 'prioridad' => 'Alta',
                'asunto' => 'Pérdida de conectividad en un segmento de red',
                'descripcion' => 'Un segmento de la red del laboratorio dejó de proporcionar conectividad a los equipos conectados.',
                'observacion' => null, 'solucion' => [
                    'diagnostico' => 'Se encontró un cable de enlace deteriorado que ocasionaba pérdida de comunicación.',
                    'acciones_realizadas' => 'Se sustituyó el cable, se verificó el estado del puerto y se realizaron pruebas de conectividad.',
                    'solucion' => 'El segmento recuperó la conectividad y las pruebas de comunicación finalizaron correctamente.',
                ],
            ],
            [
                'folio' => 'DEMO-TS-012', 'dias' => 145, 'estado' => 'Cerrado',
                'equipo' => 'DEMO-EQ-006', 'categoria' => 'Software', 'prioridad' => 'Baja',
                'asunto' => 'Configuración de aplicaciones institucionales',
                'descripcion' => 'Se solicitó configurar las aplicaciones institucionales necesarias para las actividades del área.',
                'observacion' => null, 'solucion' => [
                    'diagnostico' => 'El equipo no contaba con la configuración estándar de aplicaciones requerida por el área.',
                    'acciones_realizadas' => 'Se instalaron las aplicaciones autorizadas y se verificaron sus parámetros básicos.',
                    'solucion' => 'Las aplicaciones quedaron configuradas y se validó su apertura con el usuario solicitante.',
                ],
            ],
        ];
    }

    private function protegerEjecucionEnProduccion(): void
    {
        if (! app()->environment('production')) {
            return;
        }

        $ejecucionForzada = $this->command !== null
            && method_exists($this->command, 'option')
            && (bool) $this->command->option('force');

        if (! $ejecucionForzada) {
            throw new RuntimeException(
                'DemoDataSeeder está protegido en producción. Para ejecutarlo intencionalmente utiliza db:seed --class=Database\\Seeders\\DemoDataSeeder --force.'
            );
        }
    }
}
