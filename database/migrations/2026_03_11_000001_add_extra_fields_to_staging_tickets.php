<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staging_tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('staging_tickets', 'name')) {
                $table->string('name', 255)->nullable()->after('cc_emails');
            }
            if (!Schema::hasColumn('staging_tickets', 'no_hp')) {
                $table->string('no_hp', 255)->nullable()->after('name');
            }
            if (!Schema::hasColumn('staging_tickets', 'module')) {
                $table->string('module', 255)->nullable()->after('no_hp');
            }
            if (!Schema::hasColumn('staging_tickets', 'client')) {
                $table->string('client', 255)->nullable()->after('module');
            }
        });
    }

    public function down(): void
    {
        Schema::table('staging_tickets', function (Blueprint $table) {
            $table->dropColumn(array_filter(
                ['name', 'no_hp', 'module', 'client'],
                fn($col) => Schema::hasColumn('staging_tickets', $col)
            ));
        });
    }
};
