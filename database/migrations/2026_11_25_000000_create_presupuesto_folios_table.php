<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presupuesto_folios', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 16);
            $table->char('periodo', 7);
            $table->unsignedBigInteger('ultimo_consecutivo')->default(0);
            $table->unique(['tipo', 'periodo']);
        });

        Schema::table('presupuestos', function (Blueprint $table) {
            $table->foreignId('folio_serie_id')->nullable()->constrained('presupuesto_folios');
        });
    }

    public function down(): void
    {
        Schema::table('presupuestos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('folio_serie_id');
        });

        Schema::dropIfExists('presupuesto_folios');
    }
};
