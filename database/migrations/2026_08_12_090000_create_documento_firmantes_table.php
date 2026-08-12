<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documento_firmantes', function (Blueprint $table) {
            $table->id();
            $table->string('documento', 80);
            $table->string('campo', 80);
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique(['documento', 'campo']);
            $table->index(['documento', 'activo']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('documento_firmantes');
    }
};
