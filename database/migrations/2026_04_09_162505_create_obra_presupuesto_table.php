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
       Schema::create('obra_presupuesto', function (Blueprint $table) {
    $table->id();
    $table->foreignId('obra_id')->constrained('obras')->onDelete('cascade');
    $table->foreignId('presupuesto_id')->constrained('presupuestos')->onDelete('cascade');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('obra_presupuesto');
    }
};
