<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Construye indicadores complementarios para el centro de análisis de reportes.
 * Todas las agrupaciones se calculan sobre el mismo conjunto de filtros del reporte.
 */
class ReporteAtencionAnaliticaService
{
    public function build(array $filtros): array
    {
        $consulta = $this->construirConsulta($filtros);

        return [
            'tendencia' => $this->construirTendencia($consulta, $filtros),
            'categorias' => $this->agruparPorCatalogo($consulta, 'categorias', 'categoria_id'),
            'prioridades' => $this->agruparPorCatalogo($consulta, 'prioridades', 'prioridad_id'),
        ];
    }

    private function construirConsulta(array $filtros): Builder
    {
        return Ticket::query()
            ->when(
                ! empty($filtros['fecha_inicio']),
                fn (Builder $query) => $query->whereDate('tickets.creado_en', '>=', $filtros['fecha_inicio'])
            )
            ->when(
                ! empty($filtros['fecha_fin']),
                fn (Builder $query) => $query->whereDate('tickets.creado_en', '<=', $filtros['fecha_fin'])
            )
            ->when(
                ! empty($filtros['estado_id']),
                fn (Builder $query) => $query->where('tickets.estado_id', (int) $filtros['estado_id'])
            )
            ->when(
                ! empty($filtros['categoria_id']),
                fn (Builder $query) => $query->where('tickets.categoria_id', (int) $filtros['categoria_id'])
            )
            ->when(
                ! empty($filtros['prioridad_id']),
                fn (Builder $query) => $query->where('tickets.prioridad_id', (int) $filtros['prioridad_id'])
            )
            ->when(
                ! empty($filtros['tecnico_id']),
                fn (Builder $query) => $query->where('tickets.tecnico_id', (int) $filtros['tecnico_id'])
            );
    }

    /** @return array<int, array{nombre: string, total: int}> */
    private function agruparPorCatalogo(Builder $consulta, string $tabla, string $llave): array
    {
        $filas = (clone $consulta)
            ->leftJoin($tabla, "tickets.{$llave}", '=', "{$tabla}.id")
            ->selectRaw("COALESCE({$tabla}.nombre, 'Sin clasificación') as nombre, COUNT(tickets.id) as total")
            ->groupBy("{$tabla}.id", "{$tabla}.nombre")
            ->orderByDesc('total')
            ->orderBy('nombre')
            ->limit(6)
            ->get();

        return $filas
            ->map(fn ($fila): array => [
                'nombre' => (string) $fila->nombre,
                'total' => (int) $fila->total,
            ])
            ->all();
    }

    /** @return array<int, array{periodo: string, etiqueta: string, total: int}> */
    private function construirTendencia(Builder $consulta, array $filtros): array
    {
        $fechaFin = ! empty($filtros['fecha_fin'])
            ? Carbon::createFromFormat('Y-m-d', $filtros['fecha_fin'])->endOfMonth()
            : now()->endOfMonth();

        $fechaInicio = ! empty($filtros['fecha_inicio'])
            ? Carbon::createFromFormat('Y-m-d', $filtros['fecha_inicio'])->startOfMonth()
            : $fechaFin->copy()->subMonths(5)->startOfMonth();

        if ($fechaInicio->greaterThan($fechaFin)) {
            return [];
        }

        // La gráfica conserva una lectura legible de hasta doce meses.
        if ($fechaInicio->diffInMonths($fechaFin) > 11) {
            $fechaInicio = $fechaFin->copy()->subMonths(11)->startOfMonth();
        }

        $expresionPeriodo = match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', tickets.creado_en)",
            'pgsql' => "TO_CHAR(tickets.creado_en, 'YYYY-MM')",
            default => "DATE_FORMAT(tickets.creado_en, '%Y-%m')",
        };

        $filas = (clone $consulta)
            ->whereBetween('tickets.creado_en', [
                $fechaInicio->copy()->startOfMonth(),
                $fechaFin->copy()->endOfMonth(),
            ])
            ->selectRaw("{$expresionPeriodo} as periodo, COUNT(tickets.id) as total")
            ->groupByRaw($expresionPeriodo)
            ->orderBy('periodo')
            ->get()
            ->keyBy('periodo');

        $resultado = [];
        $cursor = $fechaInicio->copy()->startOfMonth();

        while ($cursor->lessThanOrEqualTo($fechaFin)) {
            $periodo = $cursor->format('Y-m');
            $resultado[] = [
                'periodo' => $periodo,
                'etiqueta' => $cursor->locale('es')->translatedFormat('M Y'),
                'total' => (int) ($filas->get($periodo)->total ?? 0),
            ];
            $cursor->addMonth();
        }

        return $resultado;
    }
}
