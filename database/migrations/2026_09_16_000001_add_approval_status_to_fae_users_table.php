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
        Schema::table('fae_users', function (Blueprint $table) {
            $table->string('fae_code')->nullable()->change();
            $table->string('status')->default('approved')->after('fae_code')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fae_users', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
