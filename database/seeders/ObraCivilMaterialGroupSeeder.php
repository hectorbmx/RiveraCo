<?php

namespace Database\Seeders;

use App\Models\ObraCivilMaterialGroup;
use Illuminate\Database\Seeder;

class ObraCivilMaterialGroupSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->groups() as $group) {
            ObraCivilMaterialGroup::updateOrCreate(
                ['code' => $group['code']],
                $group
            );
        }
    }

    private function groups(): array
    {
        return [
            $this->group('acero_ptr_perfiles_tubulares', 'Acero PTR y perfiles tubulares', 'acero_ptr', null, ['04007'], ['acero', 'ptr', 'perfil', 'tubular'], ['required_any' => ['ptr', 'perfil tubular', 'perfiles tubulares']]),
            $this->group('acero_refuerzo_fy4200', 'Acero de refuerzo Fy 4200 Kg/cm2', 'acero_refuerzo', 'fy4200', ['0415'], ['acero', 'refuerzo', 'fy', '4200'], ['required_terms' => ['acero', 'refuerzo'], 'grade_patterns' => ['fy\\s*=?\\s*4[,]?200', 'f[\']?y\\s*=?\\s*4[,]?200'], 'reject_patterns' => ['2[,]?530', '2530']]),
            $this->group('acero_refuerzo_fy2530', 'Acero de refuerzo Fy 2530 Kg/cm2', 'acero_refuerzo', 'fy2530', ['04151'], ['acero', 'refuerzo', 'fy', '2530'], ['required_terms' => ['acero', 'refuerzo'], 'grade_patterns' => ['fy\\s*=?\\s*2[,]?530', 'fy\\s*=?\\s*2530', 'f[\']?y\\s*=?\\s*2[,]?530'], 'reject_patterns' => ['4[,]?200', '4200']]),
            $this->group('acero_comercial', 'Acero comercial', 'acero_comercial', null, ['0527'], ['acero', 'comercial'], ['required_terms' => ['acero', 'comercial']]),
            $this->group('acero_tubular_estructural', 'Acero tubular y/o estructural', 'acero_tubular_estructural', null, ['0560'], ['acero', 'tubular', 'estructural', 'hss'], ['required_terms' => ['acero'], 'required_any' => ['tubular', 'estructural', 'hss']]),
            $this->group('acero_estructural_astm_a36_fy2530', 'Acero estructural ASTM A36 Fy 2530', 'acero_estructural', 'a36_fy2530', ['AC-A36P'], ['acero', 'estructural', 'astm', 'a36', '2530'], ['required_terms' => ['acero'], 'required_any' => ['a36', 'astm a36', 'astma-36'], 'grade_patterns' => ['fy\\s*=?\\s*2[,]?530', 'f[\']?y\\s*=?\\s*2530']]),
            $this->group('acero_estructural_astm_a572_a992_fy3515', 'Acero estructural ASTM A572/A992 Fy 3515', 'acero_estructural', 'a572_a992_fy3515', ['ACERO-A572'], ['acero', 'estructural', 'astm', 'a572', 'a992', '3515'], ['required_terms' => ['acero'], 'required_any' => ['a572', 'a-572', 'a992', 'a-992'], 'grade_patterns' => ['fy\\s*=?\\s*3[,]?515', 'f[\']?y\\s*=?\\s*3515']]),
            $this->group('acero_comercial_herreria', 'Acero comercial para herreria', 'acero_comercial_herreria', null, ['ACH'], ['acero', 'comercial', 'herreria', 'redondo', 'angulo', 'solera'], ['required_terms' => ['acero', 'comercial'], 'required_any' => ['herreria', 'redondos', 'angulos', 'soleras']]),
            $this->group('polin_monten', 'Polin Monten', 'polin_monten', null, ['ACPM'], ['polin', 'monten'], ['required_any' => ['polin', 'monten']]),
            $this->group('perfiles_or_oc_tubos_redondos', 'Perfiles OR, OC y tubos redondos', 'perfiles_or_oc_tubos', null, ['ACTR'], ['or', 'oc', 'tubos', 'redondos', 'perfiles'], ['required_any' => ['acero or', 'acero oc', 'tubos redondos', 'tubo redondo']]),
            $this->group('acero_redondo_liso_cold_rolled', 'Acero redondo liso cold rolled', 'acero_redondo_liso', 'cold_rolled', ['ARL'], ['acero', 'redondo', 'liso', 'cold', 'rolled'], ['required_terms' => ['redondo', 'liso'], 'required_any' => ['cold rolled', 'cold-rolled']]),
        ];
    }

    private function group(string $code, string $name, string $family, ?string $grade, array $sourceCodes, array $keywords, array $matchRules): array
    {
        return [
            'code' => $code,
            'name' => $name,
            'family' => $family,
            'grade' => $grade,
            'source_codes' => $sourceCodes,
            'keywords' => $keywords,
            'match_rules' => $matchRules,
            'budget_units' => ['KG', 'TON'],
            'is_active' => true,
            'metadata' => ['source' => 'initial_civil_material_groups'],
        ];
    }
}
