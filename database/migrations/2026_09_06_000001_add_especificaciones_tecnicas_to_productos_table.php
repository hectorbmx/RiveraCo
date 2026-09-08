<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            if (! Schema::hasColumn('productos', 'especificaciones_tecnicas')) {
                $table->json('especificaciones_tecnicas')->nullable()->after('punto_reorden');
            }
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            if (Schema::hasColumn('productos', 'especificaciones_tecnicas')) {
                $table->dropColumn('especificaciones_tecnicas');
            }
        });
    }
};
