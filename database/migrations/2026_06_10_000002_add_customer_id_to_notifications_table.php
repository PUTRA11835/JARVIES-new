<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('notifications', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable()->after('employee_id');
                $table->index(['customer_id', 'is_read'], 'notif_customer_unread_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            if (Schema::hasColumn('notifications', 'customer_id')) {
                $table->dropIndex('notif_customer_unread_idx');
                $table->dropColumn('customer_id');
            }
        });
    }
};
