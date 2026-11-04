<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maquina_preventivo_referencias', function (Blueprint $table) {
            $table->decimal('meta_horometro', 10, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('maquina_preventivo_referencias', fn (Blueprint $table) => $table->dropColumn('meta_horometro'));
    }
};
