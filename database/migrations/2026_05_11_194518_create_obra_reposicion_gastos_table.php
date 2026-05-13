<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obra_reposicion_gastos', function (Blueprint $table) {

            $table->id();

            $table->foreignId('obra_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedBigInteger('partida_id')->nullable();

            $table->enum('tipo_reposicion', [
                'caja_chica',
                'viaticos',
                'gastos_varios',
            ]);

            $table->string('semana')->nullable();

            $table->decimal('total', 14, 2)->default(0);

            $table->enum('estatus', [
                'borrador',
                'solicitado',
                'en_revision_area',
                'programado_area',
                'en_revision_admin',
                'pendiente_autorizacion',
                'autorizado',
                'rechazado',
                'pagado',
                'cerrado',
            ])->default('solicitado');

            $table->text('observaciones')->nullable();

            $table->unsignedBigInteger('solicitado_por')->nullable();
            $table->timestamp('solicitado_at')->nullable();

            $table->unsignedBigInteger('revisado_por')->nullable();
            $table->timestamp('revisado_at')->nullable();

            $table->unsignedBigInteger('aprobado_por')->nullable();
            $table->timestamp('aprobado_at')->nullable();

            $table->unsignedBigInteger('pagado_por')->nullable();
            $table->timestamp('pagado_at')->nullable();

            $table->timestamps();

            $table->index('obra_id');
            $table->index('estatus');
            $table->index('tipo_reposicion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obra_reposicion_gastos');
    }
};