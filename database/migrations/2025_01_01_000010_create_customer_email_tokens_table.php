<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_email_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('provider');                        // 'google' | 'azure'
            $table->string('provider_email');                  // email address used
            $table->string('provider_user_id')->nullable();    // provider's user ID
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamps();

            $table->unique(['customer_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_email_tokens');
    }
};
