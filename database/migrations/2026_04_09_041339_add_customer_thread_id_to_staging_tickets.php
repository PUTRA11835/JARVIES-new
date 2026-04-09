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
        Schema::table('staging_tickets', function (Blueprint $table) {
            // Gmail threadId dari sisi customer (hex format).
            // Disimpan saat store() mengirim email OAuth pertama.
            // Digunakan addComment() untuk menemukan Gmail threadId
            // tanpa butuh scope read (gmail.metadata).
            $table->string('customer_thread_id')->nullable()->after('email_thread_id');
        });
    }

    public function down(): void
    {
        Schema::table('staging_tickets', function (Blueprint $table) {
            $table->dropColumn('customer_thread_id');
        });
    }
};
