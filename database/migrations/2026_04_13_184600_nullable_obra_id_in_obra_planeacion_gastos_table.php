<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class NullableObraIdInObraPlaneacionGastosTable extends Migration
{
    public function up()
    {
        Schema::table('obra_planeacion_gastos', function (Blueprint $table) {
            // 1. Primero eliminar la FK
            $table->dropForeign(['obra_id']);
            
            // 2. Hacer nullable la columna
            $table->unsignedBigInteger('obra_id')->nullable()->change();
            
            // 3. Volver a agregar la FK pero permitiendo NULL
            $table->foreign('obra_id')
                  ->references('id')
                  ->on('obras')
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('obra_planeacion_gastos', function (Blueprint $table) {
            $table->dropForeign(['obra_id']);
            $table->unsignedBigInteger('obra_id')->nullable(false)->change();
            $table->foreign('obra_id')
                  ->references('id')
                  ->on('obras')
                  ->onDelete('cascade');
        });
    }
}