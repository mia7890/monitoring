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
        if (!Schema::hasTable('fae_users')) {
            Schema::create('fae_users', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name');
                $table->string('fae_code', 100)->unique();
                $table->string('email')->nullable();
                $table->string('department')->nullable();
                $table->string('phone', 100)->nullable();
                $table->string('profile_image')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fae_users');
    }
};
