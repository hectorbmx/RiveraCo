<?php

namespace Database\Seeders;

use App\Models\MetodoPagoEmpresa;
use Illuminate\Database\Seeder;

class MetodoPagoEmpresaSeeder extends Seeder
{
    public function run(): void
    {
        $metodos = [
            [
                'nombre' => 'Transferencia bancaria',
                'clave' => 'transferencia',
            ],
            [
                'nombre' => 'Cheque',
                'clave' => 'cheque',
            ],
            [
                'nombre' => 'Efectivo',
                'clave' => 'efectivo',
            ],
            [
                'nombre' => 'SPEI',
                'clave' => 'spei',
            ],
            [
                'nombre' => 'Tarjeta',
                'clave' => 'tarjeta',
            ],
            [
                'nombre' => 'Otro',
                'clave' => 'otro',
            ],
        ];

        foreach ($metodos as $metodo) {
            MetodoPagoEmpresa::updateOrCreate(
                ['clave' => $metodo['clave']],
                [
                    'nombre' => $metodo['nombre'],
                    'activo' => true,
                ]
            );
        }
    }
}