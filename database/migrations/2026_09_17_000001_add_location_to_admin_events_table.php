<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('admin_events') && !Schema::hasColumn('admin_events', 'location')) {
            Schema::table('admin_events', function (Blueprint $table) {
                $table->string('location', 255)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('admin_events') && Schema::hasColumn('admin_events', 'location')) {
            Schema::table('admin_events', function (Blueprint $table) {
                $table->dropColumn('location');
            });
        }
    }
};
