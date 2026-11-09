<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('obra_devoluciones', function (Blueprint $t) {
            $t->uuid('operacion_uuid')->nullable()->unique('od_solicitud_uuid_unique');
            $t->string('solicitud_hash', 64)->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('obra_devoluciones', function (Blueprint $t) {
            $t->dropUnique('od_solicitud_uuid_unique');
            $t->dropColumn(['operacion_uuid', 'solicitud_hash']);
        });
    }
};
