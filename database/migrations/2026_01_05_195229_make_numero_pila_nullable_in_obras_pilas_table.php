<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::table('obras_pilas', function (Blueprint $table) {
        $table->integer('numero_pila')->nullable()->change();
    });
}

public function down(): void
{
    Schema::table('obras_pilas', function (Blueprint $table) {
        $table->integer('numero_pila')->nullable(false)->change();
    });
}

};
