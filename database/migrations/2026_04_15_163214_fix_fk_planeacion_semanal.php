<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
 public function up(): void
{
    // Solo para prod: quitar la FK incorrecta que ya existe
    \DB::statement("
        ALTER TABLE obra_planeacion_semanal
        DROP FOREIGN KEY obra_planeacion_semanal_planeacion_gasto_id_foreign
    ");

    Schema::table('obra_planeacion_semanal', function (Blueprint $table) {
        $table->foreign('planeacion_gasto_id')
            ->references('id')
            ->on('obra_planeacion_gastos')
            ->onDelete('cascade');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('obra_planeacion_semanal', function (Blueprint $table) {
    $table->dropForeign(['planeacion_gasto_id']);

    $table->foreign('planeacion_gasto_id')
        ->references('id')
        ->on('planeacion_gastos')
        ->onDelete('cascade');
});
    }
};
