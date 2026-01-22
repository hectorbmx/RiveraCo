<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('catalogo_roles_alias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rol_id')->constrained('catalogo_roles')->cascadeOnDelete();
            $table->string('alias', 150)->unique(); // texto exacto o normalizado (tú decides)
            $table->timestamps();

            $table->index('rol_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_roles_alias');
    }
};
