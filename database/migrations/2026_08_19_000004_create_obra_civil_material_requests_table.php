<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obra_civil_material_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obra_id')->constrained('obras')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('empleado_id')->nullable();
            $table->string('folio', 50)->nullable();
            $table->string('status', 40)->default('enviada');
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('orden_compra_id')->nullable()->constrained('ordenes_compra')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['obra_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['empleado_id', 'status']);
            $table->index('folio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obra_civil_material_requests');
    }
};
