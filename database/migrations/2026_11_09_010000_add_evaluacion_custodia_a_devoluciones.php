<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('obra_devolucion_recepcion_detalles', 'evaluacion_tipo_snapshot')) Schema::table('obra_devolucion_recepcion_detalles', fn (Blueprint $t) => $t->string('evaluacion_tipo_snapshot', 40)->nullable());
        if (! Schema::hasColumn('obra_devolucion_recepcion_detalles', 'evaluacion_registrada_por')) Schema::table('obra_devolucion_recepcion_detalles', fn (Blueprint $t) => $t->unsignedBigInteger('evaluacion_registrada_por')->nullable());
        if (! Schema::hasColumn('obra_devolucion_recepcion_detalles', 'evaluacion_registrada_at')) Schema::table('obra_devolucion_recepcion_detalles', fn (Blueprint $t) => $t->timestamp('evaluacion_registrada_at')->nullable());
        if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') {
            $exists = \Illuminate\Support\Facades\DB::table('information_schema.TABLE_CONSTRAINTS')->where('CONSTRAINT_SCHEMA', \Illuminate\Support\Facades\DB::getDatabaseName())->where('TABLE_NAME', 'obra_devolucion_recepcion_detalles')->where('CONSTRAINT_NAME', 'odrd_eval_usuario_fk')->exists();
            if (! $exists) Schema::table('obra_devolucion_recepcion_detalles', fn (Blueprint $t) => $t->foreign('evaluacion_registrada_por', 'odrd_eval_usuario_fk')->references('id')->on('users')->nullOnDelete());
        }
    }

    public function down(): void
    {
        Schema::table('obra_devolucion_recepcion_detalles', function (Blueprint $table) {
            if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') $table->dropForeign('odrd_eval_usuario_fk');
            $table->dropColumn(['evaluacion_tipo_snapshot', 'evaluacion_registrada_por', 'evaluacion_registrada_at']);
        });
    }
};
