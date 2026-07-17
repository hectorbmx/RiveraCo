<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('phone_calls', function (Blueprint $table) {
            $table->string('extension_snapshot', 30)->nullable()->after('user_id');
            $table->string('extension_name_snapshot')->nullable()->after('extension_snapshot');
            $table->string('user_name_snapshot')->nullable()->after('extension_name_snapshot');
            $table->foreignId('matched_phone_number_id')->nullable()->after('user_name_snapshot')->constrained('telephony_phone_numbers')->nullOnDelete();
            $table->string('phoneable_type')->nullable()->after('matched_phone_number_id');
            $table->unsignedBigInteger('phoneable_id')->nullable()->after('phoneable_type');
            $table->string('phoneable_name')->nullable()->after('phoneable_id');
            $table->string('matched_number', 30)->nullable()->after('phoneable_name');
            $table->string('match_status', 30)->nullable()->after('matched_number')->index();

            $table->index(['phoneable_type', 'phoneable_id']);
        });
    }

    public function down(): void
    {
        Schema::table('phone_calls', function (Blueprint $table) {
            $table->dropForeign(['matched_phone_number_id']);
            $table->dropIndex(['phoneable_type', 'phoneable_id']);
            $table->dropColumn([
                'extension_snapshot',
                'extension_name_snapshot',
                'user_name_snapshot',
                'matched_phone_number_id',
                'phoneable_type',
                'phoneable_id',
                'phoneable_name',
                'matched_number',
                'match_status',
            ]);
        });
    }
};