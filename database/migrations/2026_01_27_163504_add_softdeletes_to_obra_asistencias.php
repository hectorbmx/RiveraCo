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
        Schema::table('obras_asistencias', function (Blueprint $table) {
            $table->softDeletes(); // deleted_at

            // auditoría de borrado (opcional pero MUY útil)
            $table->foreignId('deleted_by_user_id')
                ->nullable()
                ->after('registrado_por_user_id')
                ->constrained('users');

            $table->string('delete_reason', 255)
                ->nullable()
                ->after('deleted_by_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
   public function down(): void
    {
        Schema::table('obras_asistencias', function (Blueprint $table) {
            $table->dropConstrainedForeignId('deleted_by_user_id');
            $table->dropColumn('delete_reason');
            $table->dropSoftDeletes();
        });
    }
};
