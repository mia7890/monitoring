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
        if (!Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table) {
                $table->increments('id');
                $table->string('department_name')->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('fae_users') && !Schema::hasColumn('fae_users', 'department_id')) {
            Schema::table('fae_users', function (Blueprint $table) {
                $table->unsignedInteger('department_id')->nullable();
                $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete()->cascadeOnUpdate();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('fae_users') && Schema::hasColumn('fae_users', 'department_id')) {
            Schema::table('fae_users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('department_id');
            });
        }

        Schema::dropIfExists('departments');
    }
};
