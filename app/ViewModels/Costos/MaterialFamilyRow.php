<?php

namespace App\ViewModels\Costos;

use App\Models\ObraCivilMaterialGroup;
use Illuminate\Support\Str;

class MaterialFamilyRow
{
    public function __construct(
        public readonly int $id,
        public readonly string $code,
        public readonly string $name,
        public readonly string $family,
        public readonly ?string $grade,
        public readonly array $budgetUnits,
        public readonly bool $isActive,
        public readonly int $childrenCount,
        public readonly int $activeChildrenCount,
        public readonly int $pendingValidationCount,
        public readonly int $sourceCodesCount,
        public readonly int $keywordsCount,
        public readonly string $statusLabel,
        public readonly string $statusClass,
    ) {
    }

    public static function fromGroup(ObraCivilMaterialGroup $group): self
    {
        $childrenCount = (int) ($group->commercial_materials_count ?? 0);
        $activeChildrenCount = (int) ($group->active_children_count ?? 0);
        $pendingValidationCount = (int) ($group->pending_validation_count ?? 0);

        [$statusLabel, $statusClass] = self::statusFor($group->is_active, $activeChildrenCount, $pendingValidationCount);

        return new self(
            id: (int) $group->id,
            code: (string) $group->code,
            name: (string) $group->name,
            family: (string) $group->family,
            grade: $group->grade,
            budgetUnits: $group->budget_units ?? [],
            isActive: (bool) $group->is_active,
            childrenCount: $childrenCount,
            activeChildrenCount: $activeChildrenCount,
            pendingValidationCount: $pendingValidationCount,
            sourceCodesCount: count($group->source_codes ?? []),
            keywordsCount: count($group->keywords ?? []),
            statusLabel: $statusLabel,
            statusClass: $statusClass,
        );
    }

    public function budgetUnitsText(): string
    {
        return empty($this->budgetUnits) ? '-' : implode(', ', $this->budgetUnits);
    }

    public function gradeText(): string
    {
        return filled($this->grade) ? Str::upper((string) $this->grade) : '-';
    }

    private static function statusFor(bool $isActive, int $activeChildrenCount, int $pendingValidationCount): array
    {
        if (! $isActive) {
            return ['Inactiva', 'bg-slate-100 text-slate-600'];
        }

        if ($activeChildrenCount === 0) {
            return ['Sin hijos activos', 'bg-amber-100 text-amber-800'];
        }

        if ($pendingValidationCount > 0) {
            return ['Con pendientes', 'bg-orange-100 text-orange-800'];
        }

        return ['Lista', 'bg-emerald-100 text-emerald-700'];
    }
}
