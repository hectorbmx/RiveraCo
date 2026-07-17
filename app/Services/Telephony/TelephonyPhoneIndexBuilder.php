<?php

namespace App\Services\Telephony;

use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\Proveedor;

class TelephonyPhoneIndexBuilder
{
    public function __construct(private PhoneNumberNormalizer $normalizer)
    {
    }

    public function rows(): array
    {
        return array_values(array_filter([
            ...$this->clientes(),
            ...$this->proveedores(),
            ...$this->empleados(),
        ]));
    }

    private function clientes(): array
    {
        return Cliente::query()
            ->select(['id', 'nombre_comercial', 'razon_social', 'telefono', 'activo'])
            ->get()
            ->map(fn (Cliente $cliente) => $this->row(
                model: $cliente,
                sourceColumn: 'telefono',
                rawNumber: $cliente->telefono,
                label: 'Cliente',
                displayName: $cliente->nombre_comercial ?: $cliente->razon_social,
                isPrimary: true,
                metadata: [
                    'entity' => 'cliente',
                    'activo' => (bool) ($cliente->activo ?? true),
                ]
            ))
            ->all();
    }

    private function proveedores(): array
    {
        $rows = [];

        Proveedor::query()
            ->select(['id', 'nombre', 'razon_social', 'telefono', 'nombre_contacto', 'telefono_contacto', 'activo'])
            ->chunkById(200, function ($proveedores) use (&$rows) {
                foreach ($proveedores as $proveedor) {
                    $displayName = $proveedor->nombre ?: $proveedor->razon_social;

                    $rows[] = $this->row(
                        model: $proveedor,
                        sourceColumn: 'telefono',
                        rawNumber: $proveedor->telefono,
                        label: 'Proveedor',
                        displayName: $displayName,
                        isPrimary: true,
                        metadata: [
                            'entity' => 'proveedor',
                            'activo' => (bool) ($proveedor->activo ?? true),
                        ]
                    );

                    $rows[] = $this->row(
                        model: $proveedor,
                        sourceColumn: 'telefono_contacto',
                        rawNumber: $proveedor->telefono_contacto,
                        label: 'Contacto proveedor',
                        displayName: $proveedor->nombre_contacto ?: $displayName,
                        isPrimary: false,
                        metadata: [
                            'entity' => 'proveedor',
                            'contacto' => $proveedor->nombre_contacto,
                            'activo' => (bool) ($proveedor->activo ?? true),
                        ]
                    );
                }
            });

        return $rows;
    }

    private function empleados(): array
    {
        $rows = [];

        Empleado::query()
            ->select(['id_Empleado', 'Nombre', 'Apellidos', 'Telefono', 'Celular', 'Puesto', 'Estatus'])
            ->chunkById(200, function ($empleados) use (&$rows) {
                foreach ($empleados as $empleado) {
                    $displayName = trim(($empleado->Nombre ?? '') . ' ' . ($empleado->Apellidos ?? ''));

                    $rows[] = $this->row(
                        model: $empleado,
                        sourceColumn: 'Telefono',
                        rawNumber: $empleado->Telefono,
                        label: 'Empleado telefono',
                        displayName: $displayName,
                        isPrimary: false,
                        metadata: [
                            'entity' => 'empleado',
                            'puesto' => $empleado->Puesto,
                            'estatus' => $empleado->Estatus,
                        ]
                    );

                    $rows[] = $this->row(
                        model: $empleado,
                        sourceColumn: 'Celular',
                        rawNumber: $empleado->Celular,
                        label: 'Empleado celular',
                        displayName: $displayName,
                        isPrimary: true,
                        metadata: [
                            'entity' => 'empleado',
                            'puesto' => $empleado->Puesto,
                            'estatus' => $empleado->Estatus,
                        ]
                    );
                }
            }, 'id_Empleado');

        return $rows;
    }

    private function row($model, string $sourceColumn, ?string $rawNumber, string $label, ?string $displayName, bool $isPrimary, array $metadata): ?array
    {
        $normalized = $this->normalizer->normalize($rawNumber);

        if (!$normalized) {
            return null;
        }

        return [
            'phoneable_type' => $model::class,
            'phoneable_id' => $model->getKey(),
            'source_table' => $model->getTable(),
            'source_column' => $sourceColumn,
            'label' => $label,
            'raw_number' => trim((string) $rawNumber),
            'normalized_number' => $normalized,
            'display_name' => $displayName ?: null,
            'is_primary' => $isPrimary,
            'is_active' => true,
            'metadata' => $metadata,
        ];
    }
}