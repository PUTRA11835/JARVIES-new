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
            if (!Schema::hasColumn('staging_tickets', 'attachment_names')) {
                $table->text('attachment_names')->nullable()->after('has_attachments');
            }
        });
    }

    public function down(): void
    {
        Schema::table('staging_tickets', function (Blueprint $table) {
            if (Schema::hasColumn('staging_tickets', 'attachment_names')) {
                $table->dropColumn('attachment_names');
            }
        });
    }
};
