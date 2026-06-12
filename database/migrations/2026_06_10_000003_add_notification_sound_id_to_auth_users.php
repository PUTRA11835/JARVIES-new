<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auth_users', function (Blueprint $table) {
            if (!Schema::hasColumn('auth_users', 'notification_sound_id')) {
                $table->unsignedBigInteger('notification_sound_id')
                      ->nullable()
                      ->after('is_active');

                $table->foreign('notification_sound_id')
                      ->references('id')
                      ->on('notification_sounds')
                      ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('auth_users', function (Blueprint $table) {
            if (Schema::hasColumn('auth_users', 'notification_sound_id')) {
                $table->dropForeign(['notification_sound_id']);
                $table->dropColumn('notification_sound_id');
            }
        });
    }
};
