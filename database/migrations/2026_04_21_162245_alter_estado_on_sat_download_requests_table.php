<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE sat_download_requests MODIFY estado VARCHAR(50) NULL");
    }

    public function down(): void
    {
        // Si sabes el enum original, aquí lo puedes restaurar.
    }
};