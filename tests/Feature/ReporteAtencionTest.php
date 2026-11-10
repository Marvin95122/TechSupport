<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\EstadoTicket;
use App\Models\Prioridad;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\CatalogosTicketsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ReporteAtencionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosTicketsSeeder::class);
    }

    private function usuario(string $rol = 'solicitante', array $atributos = []): User
    {
        return User::factory()->create(array_merge([
            'role' => $rol,
            'is_active' => true,
        ], $atributos));
    }

    private function ticket(string $folio, array $datos = []): Ticket
    {
        $solicitante = $datos['solicitante'] ?? $this->usuario('solicitante');
        $tecnico = $datos['tecnico'] ?? null;
        $categoria = $datos['categoria'] ?? 'Hardware';
        $prioridad = $datos['prioridad'] ?? 'Alta';
        $estado = $datos['estado'] ?? 'Pendiente';

        $ticket = Ticket::create([
            'folio' => $folio,
            'solicitante_id' => $solicitante->id,
            'categoria_id' => Categoria::query()->where('nombre', $categoria)->firstOrFail()->id,
            'prioridad_id' => Prioridad::query()->where('nombre', $prioridad)->firstOrFail()->id,
            'estado_id' => EstadoTicket::query()->where('nombre', $estado)->firstOrFail()->id,
            'asunto' => $datos['asunto'] ?? 'Incidencia '.$folio,
            'descripcion' => 'Descripción de prueba para el reporte de atención.',
        ]);

        $actualizaciones = [];

        if ($tecnico instanceof User) {
            $actualizaciones['tecnico_id'] = $tecnico->id;
            $actualizaciones['asignado_en'] = $datos['asignado_en'] ?? now();
        }

        foreach (['creado_en', 'resuelto_en', 'cerrado_en'] as $campo) {
            if (array_key_exists($campo, $datos)) {
                $actualizaciones[$campo] = $datos[$campo];
            }
        }

        if ($actualizaciones !== []) {
            $ticket->forceFill($actualizaciones)->save();
        }

        return $ticket->refresh();
    }

    public function test_administrador_puede_consultar_el_reporte_general(): void
    {
        $administrador = $this->usuario('administrador');
        $ticket = $this->ticket('TS-REPORTE-001');

        $this->actingAs($administrador)
            ->get(route('admin.reportes.atencion'))
            ->assertOk()
            ->assertSee('Reporte general de atención')
            ->assertSee('Total de solicitudes')
            ->assertSee($ticket->folio);
    }

    public function test_usuario_no_administrador_no_puede_consultar_el_reporte(): void
    {
        $this->actingAs($this->usuario('tecnico'))
            ->get(route('admin.reportes.atencion'))
            ->assertForbidden();

        $this->actingAs($this->usuario('solicitante'))
            ->get(route('admin.reportes.atencion'))
            ->assertForbidden();
    }

    public function test_invitado_no_puede_consultar_el_reporte(): void
    {
        $this->get(route('admin.reportes.atencion'))
            ->assertRedirect(route('login'));
    }

    public function test_indicadores_se_calculan_sobre_los_tickets_registrados(): void
    {
        $administrador = $this->usuario('administrador');
        $tecnico = $this->usuario('tecnico');

        $this->ticket('TS-RESUMEN-PEND', ['estado' => 'Pendiente']);
        $this->ticket('TS-RESUMEN-ASIG', ['estado' => 'Asignado', 'tecnico' => $tecnico]);
        $this->ticket('TS-RESUMEN-PROC', ['estado' => 'En proceso', 'tecnico' => $tecnico]);
        $this->ticket('TS-RESUMEN-CERR', [
            'estado' => 'Cerrado',
            'tecnico' => $tecnico,
            'creado_en' => now()->subHours(5),
            'resuelto_en' => now()->subHour(),
            'cerrado_en' => now(),
        ]);

        $this->actingAs($administrador)
            ->get(route('admin.reportes.atencion'))
            ->assertOk()
            ->assertSee('data-report-metric="total" data-value="4"', false)
            ->assertSee('data-report-metric="pendientes" data-value="1"', false)
            ->assertSee('data-report-metric="en-atencion" data-value="2"', false)
            ->assertSee('data-report-metric="finalizadas" data-value="1"', false)
            ->assertSee('4 h 0 min');
    }

    public function test_filtros_por_periodo_y_tecnico_se_aplican_al_detalle(): void
    {
        $administrador = $this->usuario('administrador');
        $tecnicoCorrecto = $this->usuario('tecnico', ['name' => 'Técnico del reporte']);
        $tecnicoDiferente = $this->usuario('tecnico', ['name' => 'Otro técnico']);

        $categoriaHardware = Categoria::query()->where('nombre', 'Hardware')->firstOrFail();
        $prioridadAlta = Prioridad::query()->where('nombre', 'Alta')->firstOrFail();
        $estadoAsignado = EstadoTicket::query()->where('nombre', 'Asignado')->firstOrFail();

        $incluido = $this->ticket('TS-REPORTE-INCLUIDO', [
            'tecnico' => $tecnicoCorrecto,
            'estado' => 'Asignado',
            'categoria' => 'Hardware',
            'prioridad' => 'Alta',
            'creado_en' => now()->subDays(2),
        ]);
        $fueraPeriodo = $this->ticket('TS-REPORTE-FECHA', [
            'tecnico' => $tecnicoCorrecto,
            'estado' => 'Asignado',
            'creado_en' => now()->subDays(10),
        ]);
        $otroTecnico = $this->ticket('TS-REPORTE-TECNICO', [
            'tecnico' => $tecnicoDiferente,
            'estado' => 'Asignado',
            'creado_en' => now()->subDays(2),
        ]);
        $otraCategoria = $this->ticket('TS-REPORTE-CATEGORIA', [
            'tecnico' => $tecnicoCorrecto,
            'estado' => 'Asignado',
            'categoria' => 'Software',
            'creado_en' => now()->subDays(2),
        ]);
        $otraPrioridad = $this->ticket('TS-REPORTE-PRIORIDAD', [
            'tecnico' => $tecnicoCorrecto,
            'estado' => 'Asignado',
            'prioridad' => 'Baja',
            'creado_en' => now()->subDays(2),
        ]);
        $otroEstado = $this->ticket('TS-REPORTE-ESTADO', [
            'tecnico' => $tecnicoCorrecto,
            'estado' => 'En proceso',
            'creado_en' => now()->subDays(2),
        ]);

        $this->actingAs($administrador)
            ->get(route('admin.reportes.atencion', [
                'fecha_inicio' => now()->subDays(5)->toDateString(),
                'fecha_fin' => now()->toDateString(),
                'estado_id' => $estadoAsignado->id,
                'categoria_id' => $categoriaHardware->id,
                'prioridad_id' => $prioridadAlta->id,
                'tecnico_id' => $tecnicoCorrecto->id,
            ]))
            ->assertOk()
            ->assertSee($incluido->folio)
            ->assertDontSee($fueraPeriodo->folio)
            ->assertDontSee($otroTecnico->folio)
            ->assertDontSee($otraCategoria->folio)
            ->assertDontSee($otraPrioridad->folio)
            ->assertDontSee($otroEstado->folio);
    }

    public function test_fecha_final_anterior_a_fecha_inicial_es_rechazada(): void
    {
        $administrador = $this->usuario('administrador');

        $this->actingAs($administrador)
            ->from(route('admin.reportes.atencion'))
            ->get(route('admin.reportes.atencion', [
                'fecha_inicio' => now()->toDateString(),
                'fecha_fin' => now()->subDay()->toDateString(),
            ]))
            ->assertRedirect(route('admin.reportes.atencion'))
            ->assertSessionHasErrors('fecha_fin');
    }

    public function test_filtro_de_tecnico_rechaza_usuarios_con_otro_rol(): void
    {
        $administrador = $this->usuario('administrador');
        $solicitante = $this->usuario('solicitante');

        $this->actingAs($administrador)
            ->from(route('admin.reportes.atencion'))
            ->get(route('admin.reportes.atencion', ['tecnico_id' => $solicitante->id]))
            ->assertRedirect(route('admin.reportes.atencion'))
            ->assertSessionHasErrors('tecnico_id');
    }

    public function test_administrador_puede_abrir_la_version_completa_para_impresion(): void
    {
        $administrador = $this->usuario('administrador');
        $ticket = $this->ticket('TS-REPORTE-IMPRESION');

        $this->actingAs($administrador)
            ->get(route('admin.reportes.atencion.imprimir'))
            ->assertOk()
            ->assertSee('Imprimir / Guardar PDF')
            ->assertSee($ticket->folio);
    }

    public function test_solo_administrador_puede_abrir_la_version_de_impresion(): void
    {
        $this->actingAs($this->usuario('tecnico'))
            ->get(route('admin.reportes.atencion.imprimir'))
            ->assertForbidden();
    }

    public function test_administrador_puede_exportar_csv_con_los_resultados_filtrados(): void
    {
        $administrador = $this->usuario('administrador');
        $incluido = $this->ticket('TS-REPORTE-CSV-INCLUIDO', [
            'asunto' => 'Falla de teclado',
            'creado_en' => now()->subDay(),
        ]);
        $excluido = $this->ticket('TS-REPORTE-CSV-EXCLUIDO', [
            'creado_en' => now()->subDays(20),
        ]);

        $response = $this->actingAs($administrador)
            ->get(route('admin.reportes.atencion.exportar', [
                'fecha_inicio' => now()->subDays(5)->toDateString(),
                'fecha_fin' => now()->toDateString(),
            ]));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));

        $contenido = $response->streamedContent();
        $this->assertStringContainsString($incluido->folio, $contenido);
        $this->assertStringNotContainsString($excluido->folio, $contenido);
        $this->assertStringContainsString('Folio;Asunto;Solicitante', $contenido);
    }

    public function test_exportacion_protege_celdas_que_podrian_ejecutar_formulas(): void
    {
        $administrador = $this->usuario('administrador');
        $ticket = $this->ticket('TS-REPORTE-CSV-SEGURO', [
            'asunto' => '=HYPERLINK("https://ejemplo.test")',
        ]);

        $response = $this->actingAs($administrador)
            ->get(route('admin.reportes.atencion.exportar'));

        $contenido = $response->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $contenido);
        $this->assertStringContainsString($ticket->folio, $contenido);
    }

    public function test_solo_administrador_puede_exportar_el_reporte(): void
    {
        $this->actingAs($this->usuario('solicitante'))
            ->get(route('admin.reportes.atencion.exportar'))
            ->assertForbidden();
    }

    private function cargarLibroExcel(string $contenido): Spreadsheet
    {
        $ruta = tempnam(sys_get_temp_dir(), 'techsupport-excel-');

        if ($ruta === false) {
            $this->fail('No fue posible crear el archivo temporal.');
        }

        try {
            if (file_put_contents($ruta, $contenido) === false) {
                $this->fail('No fue posible escribir el archivo Excel.');
            }

            return IOFactory::load($ruta);
        } finally {
            if (is_file($ruta)) {
                unlink($ruta);
            }
        }
    }

    public function test_administrador_puede_exportar_excel_con_tres_hojas_y_filtros(): void
    {
        $administrador = $this->usuario('administrador');

        $incluido = $this->ticket('TS-REPORTE-XLSX-INCLUIDO', [
            'asunto' => 'Falla de teclado',
            'creado_en' => now()->subDay(),
        ]);

        $excluido = $this->ticket('TS-REPORTE-XLSX-EXCLUIDO', [
            'creado_en' => now()->subDays(20),
        ]);

        $response = $this->actingAs($administrador)
            ->get(route('admin.reportes.atencion.exportar.excel', [
                'fecha_inicio' => now()->subDays(5)->toDateString(),
                'fecha_fin' => now()->toDateString(),
            ]));

        $response->assertOk();

        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            (string) $response->headers->get('content-type')
        );

        $contenido = $response->streamedContent();

        $this->assertStringStartsWith('PK', $contenido);

        $libro = $this->cargarLibroExcel($contenido);

        try {
            $this->assertSame(
                ['Resumen', 'Solicitudes', 'Historial de atención'],
                $libro->getSheetNames()
            );

            // Hoja 1: resumen.
            $resumen = $libro->getSheetByName('Resumen');

            $this->assertNotNull($resumen);
            $this->assertSame(
                'REPORTE GENERAL DE ATENCIÓN',
                $resumen->getCell('A1')->getValue()
            );
            $this->assertSame(
                1,
                (int) $resumen->getCell('B7')->getValue()
            );

            // Hoja 2: solicitudes.
            $detalle = $libro->getSheetByName('Solicitudes');

            $this->assertNotNull($detalle);
            $this->assertSame(
                $incluido->folio,
                $detalle->getCell('A2')->getValue()
            );
            $this->assertSame(
                'Falla de teclado',
                $detalle->getCell('B2')->getValue()
            );

            $this->assertSame(2, $detalle->getHighestRow());

            // El ticket fuera del periodo no debe exportarse.
            $this->assertStringNotContainsString(
                $excluido->folio,
                $contenido
            );

            // Hoja 3: historial de atención.
            $historial = $libro->getSheetByName('Historial de atención');

            $this->assertNotNull($historial);
            $this->assertSame(
                $incluido->folio,
                $historial->getCell('A2')->getValue()
            );
            $this->assertSame(
                'Solicitud registrada',
                $historial->getCell('D2')->getValue()
            );
        } finally {
            $libro->disconnectWorksheets();
        }
    }

    public function test_excel_protege_celdas_que_podrian_ejecutar_formulas(): void
    {
        $administrador = $this->usuario('administrador');

        $asunto = '=HYPERLINK("https://ejemplo.test","Abrir")';

        $ticket = $this->ticket('TS-REPORTE-XLSX-SEGURO', [
            'asunto' => $asunto,
        ]);

        $response = $this->actingAs($administrador)
            ->get(route('admin.reportes.atencion.exportar.excel'));

        $response->assertOk();

        $libro = $this->cargarLibroExcel(
            $response->streamedContent()
        );

        try {
            $hoja = $libro->getSheetByName('Solicitudes');

            $this->assertNotNull($hoja);

            $celda = $hoja->getCell('B2');

            $this->assertSame($asunto, $celda->getValue());

            $this->assertSame(
                DataType::TYPE_STRING,
                $celda->getDataType()
            );

            $this->assertNotSame(
                DataType::TYPE_FORMULA,
                $celda->getDataType()
            );
        } finally {
            $libro->disconnectWorksheets();
        }
    }

    public function test_solo_administrador_puede_exportar_excel(): void
    {
        $ruta = route('admin.reportes.atencion.exportar.excel');

        $this->get($ruta)
            ->assertRedirect(route('login'));

        foreach (['tecnico', 'solicitante'] as $rol) {
            $this->actingAs($this->usuario($rol))
                ->get($ruta)
                ->assertForbidden();
        }
    }
}
