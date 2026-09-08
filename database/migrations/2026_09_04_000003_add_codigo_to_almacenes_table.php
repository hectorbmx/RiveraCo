<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('almacenes', function (Blueprint $table) {
            if (! Schema::hasColumn('almacenes', 'codigo')) {
                $table->string('codigo', 60)
                    ->nullable()
                    ->unique()
                    ->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('almacenes', function (Blueprint $table) {
            if (Schema::hasColumn('almacenes', 'codigo')) {
                $table->dropUnique(['codigo']);
                $table->dropColumn('codigo');
            }
        });
    }
};
