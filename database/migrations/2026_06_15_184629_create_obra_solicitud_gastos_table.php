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
        Schema::create('obra_solicitud_gastos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obra_id')->constrained('obras')->onDelete('cascade');
            $table->integer('semana');
            $table->string('estatus')->default('solicitado'); // solicitado, autorizado, pagado, rechazado
            $table->decimal('total', 15, 2)->default(0);
            
            // Auditoría
            $table->foreignId('solicitado_por')->constrained('users');
            $table->timestamp('solicitado_at')->nullable();
            
            $table->foreignId('autorizado_por')->nullable()->constrained('users');
            $table->timestamp('autorizado_at')->nullable();
            
            $table->foreignId('pagado_por')->nullable()->constrained('users');
            $table->timestamp('pagado_at')->nullable();

            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('obra_solicitud_gastos');
    }
};
