<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('presigned-action.temp_auth_code.table', 'temp_auth_codes'), function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->text('state_key');
            $table->string('subscriber_id')->nullable();
            $table->string('subscriber_type')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('presigned-action.temp_auth_code.table', 'temp_auth_codes'));
    }
};
