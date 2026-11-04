<?php

namespace App\Services\Comisiones;

use App\Models\ComisionEtapa;
use Illuminate\Validation\Rule;

/** Reglas de entrada compartidas por los controladores movil y web. */
class ComisionEtapaRules
{
    public static function rules(string $prefix = ''): array
    {
        return [
            "{$prefix}estado" => [
                'nullable',
                Rule::in([
                    ComisionEtapa::ESTADO_PENDIENTE,
                    ComisionEtapa::ESTADO_EN_PROCESO,
                    ComisionEtapa::ESTADO_COMPLETADA,
                    ComisionEtapa::ESTADO_NO_APLICA,
                ]),
            ],
            "{$prefix}hora_inicio" => ['nullable', 'date_format:H:i'],
            "{$prefix}hora_fin" => ['nullable', 'date_format:H:i'],
            "{$prefix}observaciones" => ['nullable', 'string'],
            "{$prefix}requiere_foto" => ['nullable', 'boolean'],
            "{$prefix}diametro" => ['nullable', 'numeric', 'min:0'],
            "{$prefix}cantidad" => ['nullable', 'integer', 'min:0'],
            "{$prefix}profundidad" => ['nullable', 'numeric', 'min:0'],
            "{$prefix}metros_comision" => ['nullable', 'numeric', 'min:0'],
            "{$prefix}kg_acero" => ['nullable', 'numeric', 'min:0'],
            "{$prefix}vol_bentonita" => ['nullable', 'numeric', 'min:0'],
            "{$prefix}vol_concreto" => ['nullable', 'numeric', 'min:0'],
            "{$prefix}ml_ademe_bauer" => ['nullable', 'numeric', 'min:0'],
            "{$prefix}campana_pzas" => ['nullable', 'integer', 'min:0'],
            "{$prefix}adicional" => ['nullable', 'string', 'max:100'],
            "{$prefix}personal" => ['nullable', 'array'],
            "{$prefix}personal.*.obra_empleado_id" => ['required_with:' . "{$prefix}personal", 'integer', 'exists:obra_empleado,id'],
            "{$prefix}personal.*.actividad_id" => ['nullable', 'integer', 'exists:catalogo_actividades_comision,id'],
            "{$prefix}personal.*.comisiona" => ['nullable', 'boolean'],
            "{$prefix}personal.*.importe_comision" => ['nullable', 'numeric', 'min:0'],
            "{$prefix}personal.*.notas" => ['nullable', 'string'],
        ];
    }

}
