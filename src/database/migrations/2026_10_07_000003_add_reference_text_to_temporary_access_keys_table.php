<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('temporary_access_keys', function (Blueprint $table): void {
            if (! Schema::hasColumn('temporary_access_keys', 'reference_text')) {
                $table->text('reference_text')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('temporary_access_keys', function (Blueprint $table): void {
            if (Schema::hasColumn('temporary_access_keys', 'reference_text')) {
                $table->dropColumn('reference_text');
            }
        });
    }
};
