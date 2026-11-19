<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presupuestos', function (Blueprint $table) {
            $table->unsignedInteger('consecutivo_cliente')->nullable();
            $table->unique(['cliente_id', 'consecutivo_cliente'], 'presupuestos_cliente_consecutivo_unique');
        });

        Schema::create('presupuesto_versiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('presupuesto_id')->constrained('presupuestos')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->foreignId('version_origen_id')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->text('motivo')->nullable();
            $table->json('datos');
            $table->decimal('total_venta', 15, 2);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['presupuesto_id', 'revision'], 'presupuesto_versiones_revision_unique');
            $table->unique(['presupuesto_id', 'id'], 'presupuesto_versiones_presupuesto_id_unique');
            $table->foreign(['presupuesto_id', 'version_origen_id'], 'presupuesto_versiones_origen_fk')
                ->references(['presupuesto_id', 'id'])->on('presupuesto_versiones')->restrictOnDelete();
        });

        Schema::create('presupuesto_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('presupuesto_version_id')->constrained('presupuesto_versiones')->restrictOnDelete();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo', 32);
            $table->text('nota')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presupuesto_eventos');
        Schema::dropIfExists('presupuesto_versiones');

        Schema::table('presupuestos', function (Blueprint $table) {
            $table->dropUnique('presupuestos_cliente_consecutivo_unique');
            $table->dropColumn('consecutivo_cliente');
        });
    }
};
