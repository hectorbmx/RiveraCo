<?php

namespace App\Services\Mobile\Equipos;

use Illuminate\Support\Arr;

class EquipoMecanicoPresenter
{
    public function listado(array $payload, string $tipo): array
    {
        $items = $payload['data']->getCollection();

        return [
            'ok' => $payload['ok'],
            'data' => $items->map(fn (array $item) => $tipo === 'maquina'
                ? Arr::only($item, ['id', 'codigo', 'nombre', 'tipo', 'marca', 'modelo', 'placas', 'horometro_base', 'estado', 'asignada', 'obra_activa'])
                : array_merge($this->vehiculo($item), [
                    'asignado' => $item['asignado'],
                    'asignacion_actual' => $this->asignacionVehiculo($item['asignacion_actual']),
                ]))->values(),
            'meta' => $payload['meta'],
        ];
    }

    public function detalle(array $payload, string $tipo): array
    {
        $data = $payload['data'];
        $equipo = $tipo === 'maquina'
            ? ['maquina' => Arr::only($data['maquina'], ['id', 'codigo', 'nombre', 'tipo', 'marca', 'modelo', 'numero_serie', 'placas', 'color', 'horometro_base', 'estado', 'ubicacion', 'notas']),
                'asignacion_activa' => $data['asignacion_activa']]
            : ['vehiculo' => $this->vehiculo($data['vehiculo']),
                'asignacion_actual' => $this->asignacionVehiculo($data['asignacion_actual'])];

        return ['ok' => $payload['ok'], 'data' => array_merge($equipo, [
            'mantenimientos_recientes' => $data['mantenimientos_recientes'],
            'mantenimientos_resumen' => $data['mantenimientos_resumen'],
        ])];
    }

    private function vehiculo(array $data): array
    {
        return Arr::only($data, ['id', 'marca', 'modelo', 'anio', 'color', 'placas', 'serie', 'tipo', 'estatus', 'fecha_registro', 'foto_url']);
    }

    private function asignacionVehiculo(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        return array_merge(Arr::only($data, ['id', 'vehiculo_id', 'empleado_id', 'fecha_asignacion', 'fecha_fin', 'km_inicial', 'km_final']), [
            'empleado' => $data['empleado'] ? Arr::only($data['empleado'], ['id', 'nombre']) : null,
        ]);
    }
}
