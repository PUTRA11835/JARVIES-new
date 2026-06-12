<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staging_tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('staging_tickets', 'ticket_type')) {
                $table->string('ticket_type', 100)->nullable()->after('client');
            }
            if (!Schema::hasColumn('staging_tickets', 'scale')) {
                $table->string('scale', 100)->nullable()->after('ticket_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('staging_tickets', function (Blueprint $table) {
            $table->dropColumn(array_filter(
                ['ticket_type', 'scale'],
                fn($col) => Schema::hasColumn('staging_tickets', $col)
            ));
        });
    }
};
