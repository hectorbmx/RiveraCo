<?php

namespace Database\Seeders;

use App\Models\ObraCivilCommercialMaterial;
use App\Models\ObraCivilMaterialGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use RuntimeException;

class ObraCivilCommercialMaterialSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/obra_civil_commercial_materials.json');

        if (! File::exists($path)) {
            throw new RuntimeException("No existe el archivo de seed: {$path}");
        }

        $rows = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
        $groups = ObraCivilMaterialGroup::query()->pluck('id', 'code');

        foreach ($rows as $row) {
            $groupId = $groups->get($row['group_code']);

            if (! $groupId) {
                throw new RuntimeException("No existe el grupo {$row['group_code']} para {$row['sku']}");
            }

            ObraCivilCommercialMaterial::updateOrCreate(
                ['sku' => $row['sku']],
                [
                    'obra_civil_material_group_id' => $groupId,
                    'category' => $row['category'],
                    'subcategory' => $row['subcategory'],
                    'grade' => $row['grade'],
                    'descripcion' => $row['descripcion'],
                    'medida' => $row['medida'],
                    'diametro' => $row['diametro'],
                    'calibre_espesor' => $row['calibre_espesor'],
                    'longitud' => $row['longitud'],
                    'unidad_compra' => $row['unidad_compra'],
                    'conversion_type' => $row['conversion_type'],
                    'peso_por_metro' => $row['peso_por_metro'],
                    'peso_por_pieza' => $row['peso_por_pieza'],
                    'peso_por_m2' => $row['peso_por_m2'],
                    'peso_por_rollo' => $row['peso_por_rollo'],
                    'factor_conversion' => $row['factor_conversion'],
                    'tolerance' => $row['tolerance'],
                    'validation_status' => $row['validation_status'],
                    'technical_source' => $row['technical_source'],
                    'is_active' => $row['is_active'],
                    'metadata' => $row['metadata'] + [
                        'source_partida_code' => $row['source_partida_code'],
                        'source_partida_name' => $row['source_partida_name'],
                    ],
                ]
            );
        }
    }
}
