<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only drop if the column exists (it may have already been removed)
        if (Schema::hasTable('fae_users') && Schema::hasColumn('fae_users', 'department')) {
            Schema::table('fae_users', function (Blueprint $table) {
                $table->dropColumn('department');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('fae_users') && !Schema::hasColumn('fae_users', 'department')) {
            Schema::table('fae_users', function (Blueprint $table) {
                $table->string('department')->nullable();
            });
        }
    }
};
