<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comision_etapas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('comision_id')
                ->constrained('comisiones')
                ->cascadeOnDelete();

            $table->foreignId('obra_id')
                ->constrained('obras')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('pila_id');

            $table->string('etapa', 40);
            $table->string('estado', 40)->default('pendiente');
            $table->unsignedTinyInteger('orden')->default(0);

            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();
            $table->text('observaciones')->nullable();

            $table->boolean('requiere_foto')->default(true);
            $table->timestamp('completada_at')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->foreign('pila_id')
                ->references('id')
                ->on('obras_pilas')
                ->cascadeOnDelete();

            $table->unique(['comision_id', 'etapa'], 'uniq_comision_etapa');
            $table->index(['obra_id', 'pila_id', 'estado'], 'idx_comision_etapas_pila_estado');
        });

        Schema::create('comision_etapa_personal', function (Blueprint $table) {
            $table->id();

            $table->foreignId('comision_etapa_id')
                ->constrained('comision_etapas')
                ->cascadeOnDelete();

            $table->foreignId('obra_empleado_id')
                ->constrained('obra_empleado')
                ->cascadeOnDelete();

            // empleados.id_Empleado es legacy; se guarda para consulta directa sin FK.
            $table->unsignedInteger('empleado_id')->nullable();

            $table->foreignId('rol_id')
                ->nullable()
                ->constrained('catalogo_roles')
                ->nullOnDelete();

            $table->foreignId('actividad_id')
                ->nullable()
                ->constrained('catalogo_actividades_comision')
                ->nullOnDelete();

            $table->boolean('comisiona')->default(true);
            $table->decimal('importe_comision', 12, 2)->default(0);
            $table->text('notas')->nullable();

            $table->timestamps();

            $table->unique(['comision_etapa_id', 'obra_empleado_id'], 'uniq_etapa_personal');
        });

        Schema::create('comision_etapa_fotos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('comision_etapa_id')
                ->constrained('comision_etapas')
                ->cascadeOnDelete();

            $table->string('disk', 40)->default('public');
            $table->string('path', 255);
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->text('comentario')->nullable();

            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['comision_etapa_id', 'created_at'], 'idx_etapa_fotos_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comision_etapa_fotos');
        Schema::dropIfExists('comision_etapa_personal');
        Schema::dropIfExists('comision_etapas');
    }
};
