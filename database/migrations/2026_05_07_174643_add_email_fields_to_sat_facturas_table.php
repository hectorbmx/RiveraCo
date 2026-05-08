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
    Schema::table('sat_facturas', function (Blueprint $table) {

        $table->timestamp('email_enviado_at')->nullable();

        $table->string('email_destino')->nullable();

    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sat_facturas', function (Blueprint $table) {
            //
        });
    }
};
