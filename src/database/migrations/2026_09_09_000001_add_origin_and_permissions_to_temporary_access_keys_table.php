<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('temporary_access_keys', function (Blueprint $table): void {
            if (! Schema::hasColumn('temporary_access_keys', 'permissions')) {
                $table->json('permissions')->nullable();
            }

            if (! Schema::hasColumn('temporary_access_keys', 'origin')) {
                $table->string('origin')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('temporary_access_keys', function (Blueprint $table): void {
            if (Schema::hasColumn('temporary_access_keys', 'permissions')) {
                $table->dropColumn('permissions');
            }

            if (Schema::hasColumn('temporary_access_keys', 'origin')) {
                $table->dropColumn('origin');
            }
        });
    }
};
