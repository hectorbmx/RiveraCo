<?php

namespace App\Services\ObraCivil;

use App\Models\ObraCivilCommercialMaterial;
use App\Models\ObraCivilInsumo;
use App\Models\ObraCivilMaterialGroup;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ObraCivilMaterialGroupResolver
{
    private ?Collection $activeGroups = null;

    public function resolve(ObraCivilInsumo $insumo): array
    {
        if (! $this->isResolvableMaterial($insumo)) {
            return $this->emptyResult('not_material');
        }

        $groups = $this->activeGroups();


        $matches = $groups
            ->map(fn (ObraCivilMaterialGroup $group) => $this->scoreGroup($insumo, $group))
            ->filter(fn (array $match) => $match['matched'])
            ->sortByDesc('score')
            ->values();

        if ($matches->isEmpty()) {
            return $this->emptyResult('no_match');
        }

        $best = $matches->first();
        $ties = $matches->filter(fn (array $match) => $match['score'] === $best['score']);

        if ($ties->count() > 1) {
            return [
                'matched' => false,
                'confidence' => 'ambiguous',
                'reason' => 'ambiguous_match',
                'group' => null,
                'commercial_materials' => collect(),
                'candidates' => $ties->pluck('group')->values(),
            ];
        }

        /** @var ObraCivilMaterialGroup $group */
        $group = $best['group'];

        return [
            'matched' => true,
            'confidence' => $best['confidence'],
            'reason' => $best['reason'],
            'group' => $group,
            'commercial_materials' => $group->commercialMaterials->values(),
            'candidates' => $matches->pluck('group')->values(),
        ];
    }

    public function commercialMaterialsFor(ObraCivilInsumo $insumo): Collection
    {
        $result = $this->resolve($insumo);

        return $result['matched'] ? $result['commercial_materials'] : collect();
    }


    private function activeGroups(): Collection
    {
        if ($this->activeGroups !== null) {
            return $this->activeGroups;
        }

        $this->activeGroups = ObraCivilMaterialGroup::query()
            ->where('is_active', true)
            ->with(['commercialMaterials' => fn ($query) => $query
                ->where('is_active', true)
                ->orderBy('subcategory')
                ->orderBy('medida')
                ->orderBy('calibre_espesor')
                ->orderBy('sku')])
            ->get();

        return $this->activeGroups;
    }
    private function isResolvableMaterial(ObraCivilInsumo $insumo): bool
    {
        return $insumo->is_active && $insumo->tipo === 'material';
    }

    private function scoreGroup(ObraCivilInsumo $insumo, ObraCivilMaterialGroup $group): array
    {
        $haystack = $this->normalize(trim(implode(' ', array_filter([
            $insumo->codigo,
            $insumo->concepto,
            $insumo->unidad,
        ]))));
        $unit = $this->normalizeUnit($insumo->unidad);
        $sourceCodes = collect($group->source_codes ?? [])->map(fn ($code) => $this->normalizeCode($code));
        $budgetUnits = collect($group->budget_units ?? [])->map(fn ($value) => $this->normalizeUnit($value));
        $rules = $group->match_rules ?? [];

        if ($budgetUnits->isNotEmpty() && ! $budgetUnits->contains($unit)) {
            return $this->noMatch($group, 'budget_unit_mismatch');
        }

        if ($sourceCodes->contains($this->normalizeCode($insumo->codigo))) {
            return [
                'matched' => true,
                'group' => $group,
                'score' => 1000,
                'confidence' => 'exact',
                'reason' => 'source_code',
            ];
        }

        if ($this->matchesAnyPattern($haystack, $rules['reject_patterns'] ?? [])) {
            return $this->noMatch($group, 'rejected_by_pattern');
        }

        foreach ($rules['required_terms'] ?? [] as $term) {
            if (! Str::contains($haystack, $this->normalize($term))) {
                return $this->noMatch($group, 'missing_required_term');
            }
        }

        if (! empty($rules['required_any']) && ! $this->containsAny($haystack, $rules['required_any'])) {
            return $this->noMatch($group, 'missing_required_any');
        }

        $hasGradeRules = ! empty($rules['grade_patterns']);
        if ($hasGradeRules && ! $this->matchesAnyPattern($haystack, $rules['grade_patterns'])) {
            return $this->noMatch($group, 'missing_grade_pattern');
        }

        $keywordHits = collect($group->keywords ?? [])
            ->filter(fn ($keyword) => Str::contains($haystack, $this->normalize($keyword)))
            ->count();

        if ($keywordHits === 0 && ! $hasGradeRules) {
            return $this->noMatch($group, 'no_keyword_match');
        }

        return [
            'matched' => true,
            'group' => $group,
            'score' => 100 + ($hasGradeRules ? 50 : 0) + $keywordHits,
            'confidence' => $hasGradeRules ? 'high' : 'medium',
            'reason' => $hasGradeRules ? 'rules_and_grade' : 'rules',
        ];
    }

    private function noMatch(ObraCivilMaterialGroup $group, string $reason): array
    {
        return [
            'matched' => false,
            'group' => $group,
            'score' => 0,
            'confidence' => 'none',
            'reason' => $reason,
        ];
    }

    private function emptyResult(string $reason): array
    {
        return [
            'matched' => false,
            'confidence' => 'none',
            'reason' => $reason,
            'group' => null,
            'commercial_materials' => collect(),
            'candidates' => collect(),
        ];
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (Str::contains($haystack, $this->normalize($needle))) {
                return true;
            }
        }

        return false;
    }

    private function matchesAnyPattern(string $haystack, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (@preg_match('/' . $pattern . '/i', $haystack) === 1) {
                return true;
            }
        }

        return false;
    }

    private function normalize(?string $value): string
    {
        $value = Str::ascii(Str::lower((string) $value));
        $value = str_replace([',', '.', ';', ':'], '', $value);

        return trim(preg_replace('/\s+/', ' ', $value) ?: '');
    }

    private function normalizeCode(?string $value): string
    {
        return Str::upper(trim((string) $value));
    }

    private function normalizeUnit(?string $value): string
    {
        $unit = Str::upper(trim((string) $value));

        return match ($unit) {
            'T', 'TONS', 'TONELADA', 'TONELADAS' => 'TON',
            'KGS', 'KILO', 'KILOS', 'KILOGRAMO', 'KILOGRAMOS' => 'KG',
            default => $unit,
        };
    }
}


