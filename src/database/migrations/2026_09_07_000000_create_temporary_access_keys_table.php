<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temporary_access_keys', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('whoami');
            $table->timestamp('expires_at');
            $table->morphs('accessible');
            $table->json('settings')->nullable();
            $table->json('scopes')->nullable();
            $table->timestamps();

            $table->index(['accessible_type', 'accessible_id', 'whoami'], 'presigned-action-unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temporary_access_keys');
    }
};
