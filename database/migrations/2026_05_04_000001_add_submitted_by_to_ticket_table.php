<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket', function (Blueprint $table) {
            if (!Schema::hasColumn('ticket', 'submitted_by_email')) {
                $table->string('submitted_by_email')->nullable()->after('client');
            }
            if (!Schema::hasColumn('ticket', 'submitted_by_name')) {
                $table->string('submitted_by_name')->nullable()->after('submitted_by_email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ticket', function (Blueprint $table) {
            $table->dropColumn(['submitted_by_email', 'submitted_by_name']);
        });
    }
};
