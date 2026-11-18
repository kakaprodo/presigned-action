<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('temporary_access_keys', function (Blueprint $table): void {
            if (! Schema::hasColumn('temporary_access_keys', 'accessible_type')) {
                $table->string('accessible_type')->nullable();
            } else {
                $table->string('accessible_type')->nullable()->change();
            }

            if (! Schema::hasColumn('temporary_access_keys', 'accessible_id')) {
                $table->unsignedBigInteger('accessible_id')->nullable();
            } else {
                $table->unsignedBigInteger('accessible_id')->nullable()->change();
            }

            if (! Schema::hasColumn('temporary_access_keys', 'is_independent')) {
                $table->boolean('is_independent')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('temporary_access_keys', function (Blueprint $table): void {
            if (Schema::hasColumn('temporary_access_keys', 'is_independent')) {
                $table->dropColumn('is_independent');
            }

            if (Schema::hasColumn('temporary_access_keys', 'accessible_type')) {
                $table->string('accessible_type')->nullable(false)->change();
            }

            if (Schema::hasColumn('temporary_access_keys', 'accessible_id')) {
                $table->unsignedBigInteger('accessible_id')->nullable(false)->change();
            }
        });
    }
};
