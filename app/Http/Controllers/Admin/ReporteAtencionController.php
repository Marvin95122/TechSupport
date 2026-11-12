<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\EstadoTicket;
use App\Models\Prioridad;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\TicketTimelineService;
use App\Services\ReporteAtencionAnaliticaService;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReporteAtencionController extends Controller
{
    /**
     * HU-25: presenta un resumen general de las solicitudes de soporte.
     * El acceso se limita al administrador mediante las rutas del módulo.
     */
    public function index(Request $request, ReporteAtencionAnaliticaService $analiticaService): View
    {
        $filtros = $this->validarFiltros($request);
        $consulta = $this->construirConsulta($filtros);
        $resumen = $this->construirResumen($consulta);
        $analitica = $analiticaService->build($filtros);

        $tickets = (clone $consulta)
            ->orderByDesc('creado_en')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $categorias = Categoria::query()->orderBy('nombre')->get(['id', 'nombre']);
        $prioridades = Prioridad::query()->orderBy('nivel')->get(['id', 'nombre']);
        $estados = EstadoTicket::query()->orderBy('orden')->get(['id', 'nombre']);
        $tecnicos = User::query()
            ->where('role', 'tecnico')
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);

        $periodoTexto = $this->obtenerTextoPeriodo($filtros);

        return view('admin.reportes.atencion', compact(
            'tickets',
            'filtros',
            'resumen',
            'analitica',
            'categorias',
            'prioridades',
            'estados',
            'tecnicos',
            'periodoTexto'
        ));
    }

    /**
     * HU-25: genera una versión completa del reporte para imprimir o guardar como PDF.
     * Se consultan todos los resultados que coinciden con los filtros, sin paginación.
     */
    public function imprimir(Request $request, ReporteAtencionAnaliticaService $analiticaService): View
    {
        $filtros = $this->validarFiltros($request);
        $consulta = $this->construirConsulta($filtros);
        $resumen = $this->construirResumen($consulta);
        $analitica = $analiticaService->build($filtros);

        $tickets = (clone $consulta)
            ->orderByDesc('creado_en')
            ->orderByDesc('id')
            ->get();

        $periodoTexto = $this->obtenerTextoPeriodo($filtros);
        $filtrosTexto = $this->obtenerDescripcionFiltros($filtros);

        return view('admin.reportes.atencion-imprimir', compact(
            'tickets',
            'filtros',
            'resumen',
            'analitica',
            'periodoTexto',
            'filtrosTexto'
        ));
    }

    /**
     * HU-25: exporta todos los resultados filtrados a CSV compatible con Excel.
     */
    public function exportar(Request $request): StreamedResponse
    {
        $filtros = $this->validarFiltros($request);
        $tickets = $this->construirConsulta($filtros)
            ->orderByDesc('creado_en')
            ->orderByDesc('id')
            ->get();

        $nombreArchivo = 'reporte-atencion-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($tickets): void {
            $salida = fopen('php://output', 'w');

            if ($salida === false) {
                return;
            }

            // BOM UTF-8 y delimitador para compatibilidad con Excel.
            fwrite($salida, "\xEF\xBB\xBFsep=;\r\n");

            fputcsv($salida, [
                'Folio',
                'Asunto',
                'Solicitante',
                'Correo del solicitante',
                'Técnico asignado',
                'Equipo',
                'Categoría',
                'Prioridad',
                'Estado',
                'Fecha de registro',
                'Fecha de asignación',
                'Fecha de resolución',
                'Fecha de cierre',
            ], ';', '"', '');

            foreach ($tickets as $ticket) {
                $fila = [
                    $ticket->folio,
                    $ticket->asunto,
                    $ticket->solicitante?->name ?? 'No disponible',
                    $ticket->solicitante?->email ?? '',
                    $ticket->tecnico?->name ?? 'Sin asignar',
                    $ticket->equipo?->codigo ?? 'Sin equipo',
                    $ticket->categoria?->nombre ?? 'No disponible',
                    $ticket->prioridad?->nombre ?? 'No disponible',
                    $ticket->estado?->nombre ?? 'No disponible',
                    $this->formatearFecha($ticket->creado_en),
                    $this->formatearFecha($ticket->asignado_en),
                    $this->formatearFecha($ticket->resuelto_en),
                    $this->formatearFecha($ticket->cerrado_en),
                ];

                fputcsv(
                    $salida,
                    array_map(fn ($valor): string => $this->protegerValorCsv($valor), $fila),
                    ';',
                    '"',
                    ''
                );
            }

            fclose($salida);
        }, $nombreArchivo, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
        ]);
    }


    /**
     * HU-25: genera un libro Excel con el resumen, las solicitudes
     * y el historial cronológico de atención.
     */
    public function exportarExcel(
        Request $request,
        TicketTimelineService $timelineService
    ): StreamedResponse {
        $filtros = $this->validarFiltros($request);
        $consulta = $this->construirConsulta($filtros);
        $resumen = $this->construirResumen($consulta);

        $tickets = (clone $consulta)
            ->with([
                'cambiosEstado.usuario:id,name',
                'cambiosEstado.estadoAnterior:id,nombre',
                'cambiosEstado.estadoNuevo:id,nombre',
                'observaciones.usuario:id,name',
                'soluciones.usuario:id,name',
            ])
            ->orderByDesc('creado_en')
            ->orderByDesc('id')
            ->get();

        $periodoTexto = $this->obtenerTextoPeriodo($filtros);
        $descripcionFiltros = $this->obtenerDescripcionFiltros($filtros);

        $filtrosTexto = empty($descripcionFiltros)
            ? 'Sin filtros adicionales'
            : implode(' | ', array_map(
                fn ($campo, $valor) => "{$campo}: {$valor}",
                array_keys($descripcionFiltros),
                array_values($descripcionFiltros)
            ));

        $spreadsheet = new Spreadsheet();

        $spreadsheet->getProperties()
            ->setCreator(config('app.name', 'TechSupport'))
            ->setTitle('Reporte general de atención')
            ->setSubject('Reporte de solicitudes y seguimiento')
            ->setDescription('Reporte generado desde el módulo administrativo.');

        /*
        |--------------------------------------------------------------------------
        | HOJA 1: RESUMEN
        |--------------------------------------------------------------------------
        */

        $resumenHoja = $spreadsheet->getActiveSheet();
        $resumenHoja->setTitle('Resumen');

        $resumenHoja->mergeCells('A1:B1');
        $resumenHoja->setCellValue('A1', 'REPORTE GENERAL DE ATENCIÓN');

        $resumenHoja->getStyle('A1:B1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '24476B'],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $resumenHoja->getRowDimension(1)->setRowHeight(30);

        $this->escribirFilaTexto($resumenHoja, 2, [
            'Generado el',
            now()->format('d/m/Y H:i'),
        ]);

        $this->escribirFilaTexto($resumenHoja, 3, [
            'Periodo',
            $periodoTexto,
        ]);

        $this->escribirFilaTexto($resumenHoja, 4, [
            'Filtros aplicados',
            $filtrosTexto,
        ]);

        // Indicadores generales.

        $this->escribirFilaTexto($resumenHoja, 6, [
            'Indicador',
            'Resultado',
        ]);

        $this->aplicarEstiloEncabezado($resumenHoja, 'A6:B6');

        $indicadores = [
            ['Total de solicitudes', $resumen['total']],
            ['Solicitudes pendientes', $resumen['pendientes']],
            ['Solicitudes en atención', $resumen['en_atencion']],
            ['Solicitudes finalizadas', $resumen['finalizados']],
        ];

        $fila = 7;

        foreach ($indicadores as [$nombre, $valor]) {
            $resumenHoja->setCellValue("A{$fila}", $nombre);
            $resumenHoja->setCellValue("B{$fila}", (int) $valor);
            $fila++;
        }

        $this->escribirFilaTexto($resumenHoja, 11, [
            'Tiempo promedio de resolución',
            $resumen['promedio_resolucion'],
        ]);

        // Distribución por estado.

        $this->escribirFilaTexto($resumenHoja, 14, [
            'Estado',
            'Cantidad',
        ]);

        $this->aplicarEstiloEncabezado($resumenHoja, 'A14:B14');

        $fila = 15;

        foreach ($resumen['estados'] as $estado) {
            $this->escribirFilaTexto($resumenHoja, $fila, [
                $estado['nombre'],
            ]);

            $resumenHoja->setCellValue(
                "B{$fila}",
                (int) $estado['total']
            );

            $fila++;
        }

        $resumenHoja->getColumnDimension('A')->setWidth(38);
        $resumenHoja->getColumnDimension('B')->setWidth(32);

        /*
        |--------------------------------------------------------------------------
        | HOJA 2: SOLICITUDES
        |--------------------------------------------------------------------------
        */

        $detalleHoja = $spreadsheet->createSheet();
        $detalleHoja->setTitle('Solicitudes');

        $encabezados = [
            'Folio',
            'Asunto',
            'Descripción',
            'Solicitante',
            'Correo del solicitante',
            'Técnico asignado',
            'Equipo',
            'Categoría',
            'Prioridad',
            'Estado',
            'Fecha de registro',
            'Fecha de asignación',
            'Fecha de resolución',
            'Fecha de cierre',
        ];

        $this->escribirFilaTexto($detalleHoja, 1, $encabezados);

        $fila = 2;

        foreach ($tickets as $ticket) {
            $this->escribirFilaTexto($detalleHoja, $fila, [
                $ticket->folio,
                $ticket->asunto,
                $ticket->descripcion,
                $ticket->solicitante?->name ?? 'No disponible',
                $ticket->solicitante?->email ?? '',
                $ticket->tecnico?->name ?? 'Sin asignar',
                $ticket->equipo?->codigo ?? 'Sin equipo',
                $ticket->categoria?->nombre ?? 'No disponible',
                $ticket->prioridad?->nombre ?? 'No disponible',
                $ticket->estado?->nombre ?? 'No disponible',
                $this->formatearFecha($ticket->creado_en),
                $this->formatearFecha($ticket->asignado_en),
                $this->formatearFecha($ticket->resuelto_en),
                $this->formatearFecha($ticket->cerrado_en),
            ]);

            $fila++;
        }

        $ultimaFila = max(1, $fila - 1);

        $this->aplicarEstiloEncabezado(
            $detalleHoja,
            'A1:N1'
        );

        $detalleHoja->freezePane('A2');
        $detalleHoja->setAutoFilter("A1:N{$ultimaFila}");

        $anchos = [
            'A' => 18,
            'B' => 30,
            'C' => 48,
            'D' => 25,
            'E' => 32,
            'F' => 25,
            'G' => 18,
            'H' => 20,
            'I' => 16,
            'J' => 18,
            'K' => 20,
            'L' => 20,
            'M' => 20,
            'N' => 20,
        ];

        foreach ($anchos as $columna => $ancho) {
            $detalleHoja->getColumnDimension($columna)->setWidth($ancho);
        }

        if ($ultimaFila >= 2) {
            $detalleHoja->getStyle("B2:C{$ultimaFila}")
                ->getAlignment()
                ->setWrapText(true);
        }

        /*
        |--------------------------------------------------------------------------
        | HOJA 3: HISTORIAL DE ATENCIÓN
        |--------------------------------------------------------------------------
        */

        $historialHoja = $spreadsheet->createSheet();
        $historialHoja->setTitle('Historial de atención');

        $this->escribirFilaTexto($historialHoja, 1, [
            'Folio',
            'Fecha y hora',
            'Tipo de evento',
            'Evento',
            'Detalle',
            'Responsable',
        ]);

        $fila = 2;

        foreach ($tickets as $ticket) {
            $eventos = $timelineService->build($ticket);

            foreach ($eventos as $evento) {
                $this->escribirFilaTexto($historialHoja, $fila, [
                    $ticket->folio,
                    $this->formatearFecha($evento['fecha']),
                    $evento['tipo'],
                    $evento['titulo'],
                    $evento['descripcion'],
                    $evento['autor'],
                ]);

                $fila++;
            }
        }

        $ultimaFila = max(1, $fila - 1);

        $this->aplicarEstiloEncabezado(
            $historialHoja,
            'A1:F1'
        );

        $historialHoja->freezePane('A2');
        $historialHoja->setAutoFilter("A1:F{$ultimaFila}");

        $historialHoja->getColumnDimension('A')->setWidth(18);
        $historialHoja->getColumnDimension('B')->setWidth(22);
        $historialHoja->getColumnDimension('C')->setWidth(20);
        $historialHoja->getColumnDimension('D')->setWidth(38);
        $historialHoja->getColumnDimension('E')->setWidth(65);
        $historialHoja->getColumnDimension('F')->setWidth(28);

        if ($ultimaFila >= 2) {
            $historialHoja->getStyle("E2:E{$ultimaFila}")
                ->getAlignment()
                ->setWrapText(true);
        }

        // La primera hoja será la que se muestre al abrir el archivo.

        $spreadsheet->setActiveSheetIndex(0);

        $nombreArchivo = 'reporte-atencion-'
            .now()->format('Ymd-His')
            .'.xlsx';

        return response()->streamDownload(
            function () use ($spreadsheet): void {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');

                $spreadsheet->disconnectWorksheets();
            },
            $nombreArchivo,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'no-store, private',
            ]
        );
    }


    /** Valida los parámetros antes de utilizarlos en consultas o exportaciones. */
    private function validarFiltros(Request $request): array
    {
        $validador = Validator::make($request->query(), [
            'fecha_inicio' => ['nullable', 'date_format:Y-m-d'],
            'fecha_fin' => ['nullable', 'date_format:Y-m-d'],
            'estado_id' => ['nullable', 'integer', Rule::exists('estados_ticket', 'id')],
            'categoria_id' => ['nullable', 'integer', Rule::exists('categorias', 'id')],
            'prioridad_id' => ['nullable', 'integer', Rule::exists('prioridades', 'id')],
            'tecnico_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('role', 'tecnico'),
            ],
        ]);

        $validador->after(function ($validador) use ($request): void {
            if (
                $validador->errors()->has('fecha_inicio') ||
                $validador->errors()->has('fecha_fin')
            ) {
                return;
            }

            $fechaInicio = $request->query('fecha_inicio');
            $fechaFin = $request->query('fecha_fin');

            if (
                is_string($fechaInicio) && $fechaInicio !== '' &&
                is_string($fechaFin) && $fechaFin !== '' &&
                $fechaFin < $fechaInicio
            ) {
                $validador->errors()->add(
                    'fecha_fin',
                    'La fecha final debe ser igual o posterior a la fecha inicial.'
                );
            }
        });

        return $validador->validate();
    }

    /** Construye la consulta común para pantalla, impresión y exportación. */
    private function construirConsulta(array $filtros): Builder
    {
        return Ticket::query()
            ->with([
                'solicitante:id,name,email',
                'tecnico:id,name',
                'equipo:id,codigo',
                'categoria:id,nombre',
                'prioridad:id,nombre',
                'estado:id,nombre,orden',
            ])
            ->when(
                ! empty($filtros['fecha_inicio']),
                fn (Builder $query) => $query->whereDate('creado_en', '>=', $filtros['fecha_inicio'])
            )
            ->when(
                ! empty($filtros['fecha_fin']),
                fn (Builder $query) => $query->whereDate('creado_en', '<=', $filtros['fecha_fin'])
            )
            ->when(
                ! empty($filtros['estado_id']),
                fn (Builder $query) => $query->where('estado_id', (int) $filtros['estado_id'])
            )
            ->when(
                ! empty($filtros['categoria_id']),
                fn (Builder $query) => $query->where('categoria_id', (int) $filtros['categoria_id'])
            )
            ->when(
                ! empty($filtros['prioridad_id']),
                fn (Builder $query) => $query->where('prioridad_id', (int) $filtros['prioridad_id'])
            )
            ->when(
                ! empty($filtros['tecnico_id']),
                fn (Builder $query) => $query->where('tecnico_id', (int) $filtros['tecnico_id'])
            );
    }

    /** Calcula indicadores con el mismo conjunto de filtros aplicado al detalle. */
    private function construirResumen(Builder $consulta): array
    {
        $conteos = (clone $consulta)
            ->join('estados_ticket', 'tickets.estado_id', '=', 'estados_ticket.id')
            ->select('estados_ticket.nombre as estado', DB::raw('COUNT(tickets.id) as total'))
            ->groupBy('estados_ticket.nombre')
            ->pluck('total', 'estado');

        $total = (int) (clone $consulta)->count('tickets.id');
        $pendientes = (int) ($conteos['Pendiente'] ?? 0);
        $enAtencion = (int) ($conteos['Asignado'] ?? 0) + (int) ($conteos['En proceso'] ?? 0);
        $finalizados = (int) ($conteos['Resuelto'] ?? 0) + (int) ($conteos['Cerrado'] ?? 0);

        $duraciones = (clone $consulta)
            ->whereNotNull('resuelto_en')
            ->get(['creado_en', 'resuelto_en'])
            ->map(function (Ticket $ticket): int {
                if ($ticket->creado_en === null || $ticket->resuelto_en === null) {
                    return 0;
                }

                return max(0, (int) $ticket->creado_en->diffInMinutes($ticket->resuelto_en));
            });

        $promedioMinutos = $duraciones->isEmpty()
            ? null
            : (int) round($duraciones->avg());

        $estados = EstadoTicket::query()
            ->orderBy('orden')
            ->get(['id', 'nombre', 'orden'])
            ->map(fn (EstadoTicket $estado): array => [
                'nombre' => $estado->nombre,
                'total' => (int) ($conteos[$estado->nombre] ?? 0),
            ]);

        return [
            'total' => $total,
            'pendientes' => $pendientes,
            'en_atencion' => $enAtencion,
            'finalizados' => $finalizados,
            'promedio_resolucion' => $this->formatearDuracion($promedioMinutos),
            'estados' => $estados,
        ];
    }

    private function obtenerTextoPeriodo(array $filtros): string
    {
        $inicio = ! empty($filtros['fecha_inicio'])
            ? Carbon::createFromFormat('Y-m-d', $filtros['fecha_inicio'])->format('d/m/Y')
            : null;
        $fin = ! empty($filtros['fecha_fin'])
            ? Carbon::createFromFormat('Y-m-d', $filtros['fecha_fin'])->format('d/m/Y')
            : null;

        if ($inicio !== null && $fin !== null) {
            return "Del {$inicio} al {$fin}";
        }

        if ($inicio !== null) {
            return "Desde {$inicio}";
        }

        if ($fin !== null) {
            return "Hasta {$fin}";
        }

        return 'Todos los periodos';
    }

    private function obtenerDescripcionFiltros(array $filtros): array
    {
        $descripcion = [];

        if (! empty($filtros['estado_id'])) {
            $descripcion['Estado'] = EstadoTicket::query()->find($filtros['estado_id'])?->nombre;
        }

        if (! empty($filtros['categoria_id'])) {
            $descripcion['Categoría'] = Categoria::query()->find($filtros['categoria_id'])?->nombre;
        }

        if (! empty($filtros['prioridad_id'])) {
            $descripcion['Prioridad'] = Prioridad::query()->find($filtros['prioridad_id'])?->nombre;
        }

        if (! empty($filtros['tecnico_id'])) {
            $descripcion['Técnico'] = User::query()->find($filtros['tecnico_id'])?->name;
        }

        return array_filter($descripcion);
    }

    private function formatearDuracion(?int $minutos): string
    {
        if ($minutos === null) {
            return 'Sin datos';
        }

        $horas = intdiv($minutos, 60);
        $minutosRestantes = $minutos % 60;

        return $horas > 0
            ? "{$horas} h {$minutosRestantes} min"
            : "{$minutosRestantes} min";
    }

    private function formatearFecha(mixed $fecha): string
    {
        return $fecha?->format('d/m/Y H:i') ?? '';
    }

    /** Evita que valores proporcionados por usuarios se interpreten como fórmulas en Excel. */
    private function protegerValorCsv(mixed $valor): string
    {
        $texto = (string) ($valor ?? '');

        if (preg_match('/^[\x00-\x20]*[=+\-@]/', $texto) === 1) {
            return "'{$texto}";
        }

        return $texto;
    }

    /**
     * Escribe valores como texto explícito para evitar que Excel
     * interprete contenido proporcionado por usuarios como fórmulas.
     */
    private function escribirFilaTexto(
        Worksheet $hoja,
        int $fila,
        array $valores
    ): void {
        foreach (array_values($valores) as $indice => $valor) {
            $columna = Coordinate::stringFromColumnIndex($indice + 1);

            $hoja->setCellValueExplicit(
                "{$columna}{$fila}",
                (string) ($valor ?? ''),
                DataType::TYPE_STRING
            );
        }
    }

    /**
     * Aplica el formato visual a los encabezados de las hojas.
     */
    private function aplicarEstiloEncabezado(
        Worksheet $hoja,
        string $rango
    ): void {
        $hoja->getStyle($rango)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '24476B'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        $hoja->getRowDimension(1)->setRowHeight(28);
    }
}
