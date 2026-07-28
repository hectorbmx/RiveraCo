<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('empresa_alerta_destinatarios')) {
            Schema::create('empresa_alerta_destinatarios', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_config_id')->constrained('empresa_config')->cascadeOnDelete();
                $table->string('modulo', 50)->default('vehiculos')->index('ead_modulo_idx');
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('nombre')->nullable();
                $table->string('email')->nullable();
                $table->boolean('notificar_correo')->default(true);
                $table->boolean('notificar_sistema')->default(true);
                $table->boolean('activo')->default(true);
                $table->timestamps();

                $table->index(['empresa_config_id', 'modulo', 'activo'], 'ead_empresa_modulo_activo_idx');
            });

            return;
        }

        Schema::table('empresa_alerta_destinatarios', function (Blueprint $table) {
            $table->index(['empresa_config_id', 'modulo', 'activo'], 'ead_empresa_modulo_activo_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa_alerta_destinatarios');
    }
};
