<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_devices', function (Blueprint $table) {
            $table->boolean('remember_web_session')->default(true)->after('is_default');
            $table->boolean('open_notifications_in_browser')->default(true)->after('remember_web_session');
            $table->string('notification_click_behavior', 40)->default('open_detail')->after('open_notifications_in_browser');
            $table->timestamp('trusted_until')->nullable()->after('notification_click_behavior');
        });
    }

    public function down(): void
    {
        Schema::table('agent_devices', function (Blueprint $table) {
            $table->dropColumn([
                'remember_web_session',
                'open_notifications_in_browser',
                'notification_click_behavior',
                'trusted_until',
            ]);
        });
    }
};