<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sat_empresas', function (Blueprint $table) {
            $table->string('nombre')->after('id');
            $table->string('rfc', 13)->unique()->after('nombre');
            $table->string('cer_path')->nullable()->after('rfc');
            $table->string('key_path')->nullable()->after('cer_path');
            $table->text('fiel_password')->nullable()->after('key_path');
            $table->boolean('activo')->default(true)->after('fiel_password');

            $table->index('rfc');
            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::table('sat_empresas', function (Blueprint $table) {
            $table->dropIndex(['rfc']);
            $table->dropIndex(['activo']);

            $table->dropColumn([
                'nombre',
                'rfc',
                'cer_path',
                'key_path',
                'fiel_password',
                'activo',
            ]);
        });
    }
};