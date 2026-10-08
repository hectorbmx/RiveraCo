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

            ['reporte_horas_extra', 'Reporte horas extra', 'solicita', 'Solicita', 'realiza', 'Realiza', 10],
            ['reporte_horas_extra', 'Reporte horas extra', DocumentoFirmante::AMBITO_GIRALDA, 'Giralda', DocumentoFirmante::CAMPO_VOBO, 'VoBo', 20],
            ['reporte_horas_extra', 'Reporte horas extra', DocumentoFirmante::AMBITO_GIRALDA, 'Giralda', 'autoriza', 'Autoriza', 30],
            ['reporte_horas_extra', 'Reporte horas extra', 'recibe', 'Recibe', 'recibe', 'Recibe', 40],

            [DocumentoFirmante::DOCUMENTO_REPOSICION_CAJA_CHICA, 'Reposicion caja chica', 'formato_administrativo', 'Formato administrativo', 'realizo', 'Realizo', 10],
            [DocumentoFirmante::DOCUMENTO_REPOSICION_CAJA_CHICA, 'Reposicion caja chica', 'formato_administrativo', 'Formato administrativo', DocumentoFirmante::CAMPO_VOBO, 'VoBo', 20],
            [DocumentoFirmante::DOCUMENTO_REPOSICION_CAJA_CHICA, 'Reposicion caja chica', 'formato_administrativo', 'Formato administrativo', DocumentoFirmante::CAMPO_REVISO, 'Reviso', 30],

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