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
        if (!Schema::hasTable('ticket')) {
            return; // Tabel dikelola EcoSystem, skip jika belum ada (local dev)
        }

        if (!Schema::hasColumn('ticket', 'customer_thread_id')) {
            Schema::table('ticket', function (Blueprint $table) {
                // Thread ID dari sisi customer (Gmail threadId atau Azure conversationId)
                // Berbeda dari email_thread_id yang dipakai helpdesk (Microsoft Graph conversation)
                $table->string('customer_thread_id')->nullable()->after('email_thread_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ticket') && Schema::hasColumn('ticket', 'customer_thread_id')) {
            Schema::table('ticket', function (Blueprint $table) {
                $table->dropColumn('customer_thread_id');
            });
        }
    }
};
