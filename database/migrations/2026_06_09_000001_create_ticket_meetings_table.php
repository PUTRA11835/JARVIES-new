<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_meetings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('started_by');
            $table->string('started_by_name');
            $table->string('topic')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->text('summary')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->unsignedBigInteger('start_message_id')->nullable();
            $table->unsignedBigInteger('end_message_id')->nullable();
            $table->timestamps();

            $table->foreign('ticket_id')->references('ticket_id')->on('ticket')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_meetings');
    }
};
