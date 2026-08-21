<?php

namespace App\Services\ObraCivil;

use App\Models\CivilWorkReport;
use App\Models\Obra;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ObraCivilWorkReportIndexService
{
    /**
     * @param array<string, mixed> $filters
     */
    public function list(Obra $obra, array $filters): LengthAwarePaginator
    {
        return $this->baseQuery($obra)
            ->when($this->statusFilter($filters) !== null, fn (Builder $query) => $query->where('status', $this->statusFilter($filters)))
            ->when($this->onlyApprovedPendingEstimation($filters), function (Builder $query) {
                $query->where('status', CivilWorkReport::STATUS_APROBADO)
                    ->where(function (Builder $inner) {
                        $inner->whereNull('metadata->estimation_id')
                            ->orWhere('metadata->estimation_id', '');
                    });
            })
            ->when($this->searchFilter($filters) !== null, function (Builder $query) use ($filters) {
                $search = $this->searchFilter($filters);

                $query->whereHas('items.concept', function (Builder $conceptQuery) use ($search) {
                    $conceptQuery->where('excel_code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($this->dateFilter($filters, 'date_from') !== null, function (Builder $query) use ($filters) {
                $query->whereDate('submitted_at', '>=', $this->dateFilter($filters, 'date_from'));
            })
            ->when($this->dateFilter($filters, 'date_to') !== null, function (Builder $query) use ($filters) {
                $query->whereDate('submitted_at', '<=', $this->dateFilter($filters, 'date_to'));
            })
            ->latest('submitted_at')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();
    }

    /**
     * @return array<string, int>
     */
    public function stats(Obra $obra): array
    {
        $statusCounts = CivilWorkReport::query()
            ->where('obra_id', $obra->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $approvedPendingEstimation = CivilWorkReport::query()
            ->where('obra_id', $obra->id)
            ->where('status', CivilWorkReport::STATUS_APROBADO)
            ->where(function (Builder $query) {
                $query->whereNull('metadata->estimation_id')
                    ->orWhere('metadata->estimation_id', '');
            })
            ->count();

        return [
            'total' => (int) $statusCounts->sum(),
            'pendiente' => (int) ($statusCounts[CivilWorkReport::STATUS_PENDIENTE] ?? 0),
            'en_revision' => (int) ($statusCounts[CivilWorkReport::STATUS_EN_REVISION] ?? 0),
            'aprobado' => (int) ($statusCounts[CivilWorkReport::STATUS_APROBADO] ?? 0),
            'rechazado' => (int) ($statusCounts[CivilWorkReport::STATUS_RECHAZADO] ?? 0),
            'convertido_a_estimacion' => (int) ($statusCounts[CivilWorkReport::STATUS_CONVERTIDO_A_ESTIMACION] ?? 0),
            'aprobados_pendientes_estimacion' => $approvedPendingEstimation,
        ];
    }

    private function baseQuery(Obra $obra): Builder
    {
        return CivilWorkReport::query()
            ->where('obra_id', $obra->id)
            ->with([
                'empleado',
                'user',
                'reviewedBy',
                'items.concept.partida.building',
                'items.photos',
            ]);
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function statusFilter(array $filters): ?string
    {
        $status = trim((string) ($filters['status'] ?? ''));

        return in_array($status, [
            CivilWorkReport::STATUS_PENDIENTE,
            CivilWorkReport::STATUS_EN_REVISION,
            CivilWorkReport::STATUS_APROBADO,
            CivilWorkReport::STATUS_RECHAZADO,
            CivilWorkReport::STATUS_CONVERTIDO_A_ESTIMACION,
        ], true) ? $status : null;
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function searchFilter(array $filters): ?string
    {
        $search = trim((string) ($filters['q'] ?? ''));

        return $search !== '' ? $search : null;
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function dateFilter(array $filters, string $key): ?string
    {
        $date = trim((string) ($filters[$key] ?? ''));

        if ($date === '') {
            return null;
        }

        try {
            return Carbon::parse($date)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function onlyApprovedPendingEstimation(array $filters): bool
    {
        return (string) ($filters['scope'] ?? '') === 'aprobados_pendientes_estimacion';
    }
}
