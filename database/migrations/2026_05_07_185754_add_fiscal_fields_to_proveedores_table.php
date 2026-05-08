<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFiscalFieldsToProveedoresTable extends Migration
{
    public function up()
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->string('razon_social', 255)->nullable()->after('nombre');
            $table->string('codigo_postal', 10)->nullable()->after('domicilio');
            $table->string('regimen_fiscal', 10)->nullable()->after('codigo_postal');
            $table->string('uso_cfdi_default', 10)->nullable()->after('regimen_fiscal');

            $table->string('nombre_contacto', 150)->nullable()->after('email');
            $table->string('telefono_contacto', 30)->nullable()->after('nombre_contacto');

            $table->index('rfc');
        });
    }

    public function down()
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropIndex(['rfc']);

            $table->dropColumn([
                'razon_social',
                'codigo_postal',
                'regimen_fiscal',
                'uso_cfdi_default',
                'nombre_contacto',
                'telefono_contacto',
            ]);
        });
    }
}