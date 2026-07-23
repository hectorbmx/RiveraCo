<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obra_factura_pagos', function (Blueprint $table) {
            $table->string('idempotency_key', 80)->nullable()->after('factura_source')->unique();
        });
    }

    public function down(): void
    {
        Schema::table('obra_factura_pagos', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn('idempotency_key');
        });
    }
};
