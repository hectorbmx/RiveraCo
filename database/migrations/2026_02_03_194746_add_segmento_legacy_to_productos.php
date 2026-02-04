<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->string('segmento_legacy', 120)->nullable()->after('uso_label');
            $table->index('segmento_legacy');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropIndex(['segmento_legacy']);
            $table->dropColumn('segmento_legacy');
        });
    }
};
