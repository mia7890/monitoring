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
        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'attachment')) {
                $table->text('attachment')->nullable();
            }
            if (!Schema::hasColumn('tasks', 'links')) {
                $table->text('links')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'links')) {
                $table->dropColumn('links');
            }
            if (Schema::hasColumn('tasks', 'attachment')) {
                $table->dropColumn('attachment');
            }
        });
    }
};
