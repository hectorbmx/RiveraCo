<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sat_cfdis', function (Blueprint $table) {
            $table->string('version', 10)->nullable()->after('uuid');
            $table->string('serie', 50)->nullable()->after('version');
            $table->string('folio', 50)->nullable()->after('serie');

            $table->decimal('subtotal', 18, 6)->nullable()->after('tipo_comprobante');
            $table->decimal('descuento', 18, 6)->nullable()->after('subtotal');
            $table->string('moneda', 10)->nullable()->after('total');
            $table->decimal('tipo_cambio', 18, 6)->nullable()->after('moneda');

            $table->string('forma_pago', 10)->nullable()->after('tipo_cambio');
            $table->string('metodo_pago', 10)->nullable()->after('forma_pago');
            $table->string('lugar_expedicion', 10)->nullable()->after('metodo_pago');
            $table->string('exportacion', 10)->nullable()->after('lugar_expedicion');

            $table->string('no_certificado', 50)->nullable()->after('exportacion');
            $table->longText('certificado')->nullable()->after('no_certificado');
            $table->longText('sello')->nullable()->after('certificado');

            $table->string('emisor_rfc', 13)->nullable()->after('sello');
            $table->string('emisor_nombre')->nullable()->after('emisor_rfc');
            $table->string('emisor_regimen_fiscal', 10)->nullable()->after('emisor_nombre');

            $table->string('receptor_rfc', 13)->nullable()->after('emisor_regimen_fiscal');
            $table->string('receptor_nombre')->nullable()->after('receptor_rfc');
            $table->string('receptor_domicilio_fiscal', 10)->nullable()->after('receptor_nombre');
            $table->string('receptor_regimen_fiscal', 10)->nullable()->after('receptor_domicilio_fiscal');
            $table->string('receptor_uso_cfdi', 10)->nullable()->after('receptor_regimen_fiscal');
        });
    }

    public function down(): void
    {
        Schema::table('sat_cfdis', function (Blueprint $table) {
            $table->dropColumn([
                'version',
                'serie',
                'folio',
                'subtotal',
                'descuento',
                'moneda',
                'tipo_cambio',
                'forma_pago',
                'metodo_pago',
                'lugar_expedicion',
                'exportacion',
                'no_certificado',
                'certificado',
                'sello',
                'emisor_rfc',
                'emisor_nombre',
                'emisor_regimen_fiscal',
                'receptor_rfc',
                'receptor_nombre',
                'receptor_domicilio_fiscal',
                'receptor_regimen_fiscal',
                'receptor_uso_cfdi',
            ]);
        });
    }
};