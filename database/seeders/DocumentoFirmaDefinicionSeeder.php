<?php

namespace Database\Seeders;

use App\Models\DocumentoFirmaDefinicion;
use App\Models\DocumentoFirmante;
use Illuminate\Database\Seeder;

class DocumentoFirmaDefinicionSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [DocumentoFirmante::DOCUMENTO_ORDEN_COMPRA, 'Orden de compra', DocumentoFirmante::AMBITO_GENERAL, 'General', DocumentoFirmante::CAMPO_VOBO_1, 'VoBo 1', 10],
            [DocumentoFirmante::DOCUMENTO_ORDEN_COMPRA, 'Orden de compra', DocumentoFirmante::AMBITO_GENERAL, 'General', DocumentoFirmante::CAMPO_VOBO_2, 'VoBo 2', 20],
            [DocumentoFirmante::DOCUMENTO_ORDEN_COMPRA, 'Orden de compra', DocumentoFirmante::AMBITO_GENERAL, 'General', DocumentoFirmante::CAMPO_ENTERADO, 'ENTERADO', 30],

            [DocumentoFirmante::DOCUMENTO_REPOSICION_CAJA_CHICA, 'Reposicion caja chica', DocumentoFirmante::AMBITO_REPOSICION_GASTOS_ALMACEN, 'Reposicion gastos almacen', DocumentoFirmante::CAMPO_ELABORO, 'Elaboro', 10],
            [DocumentoFirmante::DOCUMENTO_REPOSICION_CAJA_CHICA, 'Reposicion caja chica', DocumentoFirmante::AMBITO_REPOSICION_GASTOS_ALMACEN, 'Reposicion gastos almacen', DocumentoFirmante::CAMPO_VOBO, 'VoBo', 20],
            [DocumentoFirmante::DOCUMENTO_REPOSICION_CAJA_CHICA, 'Reposicion caja chica', DocumentoFirmante::AMBITO_REPOSICION_GASTOS_ALMACEN, 'Reposicion gastos almacen', DocumentoFirmante::CAMPO_AUTORIZO, 'Autorizo', 30],

            [DocumentoFirmante::DOCUMENTO_REPOSICION_CAJA_CHICA, 'Reposicion caja chica', DocumentoFirmante::AMBITO_GIRALDA, 'Giralda', DocumentoFirmante::CAMPO_ELABORO, 'Elaboro', 10],
            [DocumentoFirmante::DOCUMENTO_REPOSICION_CAJA_CHICA, 'Reposicion caja chica', DocumentoFirmante::AMBITO_GIRALDA, 'Giralda', DocumentoFirmante::CAMPO_VOBO, 'VoBo', 20],
            [DocumentoFirmante::DOCUMENTO_REPOSICION_CAJA_CHICA, 'Reposicion caja chica', DocumentoFirmante::AMBITO_GIRALDA, 'Giralda', DocumentoFirmante::CAMPO_AUTORIZO, 'Autorizo', 30],
        ];

        foreach ($rows as [$documento, $documentoLabel, $ambito, $ambitoLabel, $campo, $campoLabel, $orden]) {
            DocumentoFirmaDefinicion::updateOrCreate(
                [
                    'documento' => $documento,
                    'ambito' => $ambito,
                    'campo' => $campo,
                ],
                [
                    'documento_label' => $documentoLabel,
                    'ambito_label' => $ambitoLabel,
                    'campo_label' => $campoLabel,
                    'orden' => $orden,
                    'activo' => true,
                ]
            );
        }
    }
}