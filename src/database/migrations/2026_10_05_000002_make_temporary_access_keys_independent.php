<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('temporary_access_keys', function (Blueprint $table): void {
            $table->string('accessible_type')->nullable()->change();
            $table->unsignedBigInteger('accessible_id')->nullable()->change();
            $table->boolean('is_independent')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('temporary_access_keys', function (Blueprint $table): void {
            $table->dropColumn('is_independent');
            $table->string('accessible_type')->nullable(false)->change();
            $table->unsignedBigInteger('accessible_id')->nullable(false)->change();
        });
    }
};
