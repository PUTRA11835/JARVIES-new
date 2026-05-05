<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auth_users', function (Blueprint $table) {
            $table->boolean('can_view_all_tickets')->default(true)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('auth_users', function (Blueprint $table) {
            $table->dropColumn('can_view_all_tickets');
        });
    }
};
