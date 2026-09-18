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
        if (Schema::hasTable('task_updates')) {
            Schema::table('task_updates', function (Blueprint $table) {
                $table->text('attachment')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('task_updates')) {
            Schema::table('task_updates', function (Blueprint $table) {
                $table->string('attachment', 255)->nullable()->change();
            });
        }
    }
};
