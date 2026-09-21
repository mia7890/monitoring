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
        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                if (!Schema::hasColumn('appointments', 'start_time')) {
                    $table->time('start_time')->nullable();
                }
                if (!Schema::hasColumn('appointments', 'end_time')) {
                    $table->time('end_time')->nullable();
                }
                $table->index(['appointment_date', 'start_time', 'end_time'], 'appointments_date_time_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->dropIndex('appointments_date_time_index');
                if (Schema::hasColumn('appointments', 'end_time')) {
                    $table->dropColumn('end_time');
                }
                if (Schema::hasColumn('appointments', 'start_time')) {
                    $table->dropColumn('start_time');
                }
            });
        }
    }
};
