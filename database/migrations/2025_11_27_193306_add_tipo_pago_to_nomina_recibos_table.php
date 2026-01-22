<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('nomina_recibos', function (Blueprint $table) {
            $table->string('tipo_pago')->nullable();
        });
    }

    public function down()
    {
        Schema::table('nomina_recibos', function (Blueprint $table) {
            $table->dropColumn('tipo_pago');
        });
    }
};
