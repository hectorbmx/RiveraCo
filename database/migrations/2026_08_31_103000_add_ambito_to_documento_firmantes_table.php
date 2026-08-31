<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documento_firmantes', function (Blueprint $table) {
            $table->string('ambito', 80)->default('general')->after('documento');
        });

        Schema::table('documento_firmantes', function (Blueprint $table) {
            $table->dropUnique('documento_firmantes_documento_campo_unique');
            $table->unique(
                ['documento', 'ambito', 'campo'],
                'documento_firmantes_documento_ambito_campo_unique'
            );
            $table->index(
                ['documento', 'ambito', 'activo'],
                'documento_firmantes_documento_ambito_activo_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('documento_firmantes', function (Blueprint $table) {
            $table->dropIndex('documento_firmantes_documento_ambito_activo_index');
            $table->dropUnique('documento_firmantes_documento_ambito_campo_unique');
        });

        DB::table('documento_firmantes')
            ->where('ambito', '<>', 'general')
            ->delete();

        Schema::table('documento_firmantes', function (Blueprint $table) {
            $table->unique(['documento', 'campo']);
            $table->dropColumn('ambito');
        });
    }
};
