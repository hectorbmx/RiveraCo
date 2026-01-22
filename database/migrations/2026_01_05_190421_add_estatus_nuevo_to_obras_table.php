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
    Schema::table('obras', function (Blueprint $table) {
        if (!Schema::hasColumn('obras', 'estatus_nuevo')) {
            $table->unsignedTinyInteger('estatus_nuevo')
                  ->default(2)
                  ->after('status'); // si status no existe, me dices y lo movemos
        }
    });
}

public function down(): void
{
    Schema::table('obras', function (Blueprint $table) {
        if (Schema::hasColumn('obras', 'estatus_nuevo')) {
            $table->dropColumn('estatus_nuevo');
        }
    });
}
};
