<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE empleado_documentos
            MODIFY tipo_documento VARCHAR(150) NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE empleado_documentos
            MODIFY tipo_documento ENUM(
                'INE',
                'LICENCIA_CONDUCIR',
                'COMPROBANTE_DOMICILIO',
                'ACTA_NACIMIENTO',
                'CURP',
                'RFC',
                'NSS',
                'CONSTANCIA_FISCAL',
                'CONTRATO',
                'OTRO'
            ) NOT NULL
        ");
    }
};