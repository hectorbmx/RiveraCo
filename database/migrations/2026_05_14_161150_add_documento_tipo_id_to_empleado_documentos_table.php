<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empleado_documentos', function (Blueprint $table) {
            $table->foreignId('documento_tipo_id')
                ->nullable()
                ->after('empleado_id')
                ->constrained('empresa_documento_tipos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('empleado_documentos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('documento_tipo_id');
        });
    }
};