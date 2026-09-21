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
        if (Schema::hasTable('admin_events') && !Schema::hasColumn('admin_events', 'end_date')) {
            Schema::table('admin_events', function (Blueprint $table) {
                $table->date('end_date')->nullable()->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('admin_events') && Schema::hasColumn('admin_events', 'end_date')) {
            Schema::table('admin_events', function (Blueprint $table) {
                $table->dropColumn('end_date');
            });
        }
    }
};
