<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add composite and covering indexes for the most frequently queried
     * columns. All additions are additive; foreign-key columns already
     * have indexes created by their original migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->index('status', 'tasks_status_index');
                $table->index('deadline', 'tasks_deadline_index');
                $table->index(['fae_id', 'status'], 'tasks_fae_id_status_index');
            });
        }

        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->index('status', 'appointments_status_index');
                $table->index(['fae_id', 'status'], 'appointments_fae_id_status_index');
            });
        }

        if (Schema::hasTable('task_updates')) {
            Schema::table('task_updates', function (Blueprint $table) {
                $table->index(['task_id', 'created_at'], 'task_updates_task_id_created_at_index');
                $table->index(['author_role', 'created_at'], 'task_updates_author_role_created_at_index');
            });
        }

        if (Schema::hasTable('departments')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->index('is_active', 'departments_is_active_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropIndex('tasks_status_index');
                $table->dropIndex('tasks_deadline_index');
                $table->dropIndex('tasks_fae_id_status_index');
            });
        }

        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->dropIndex('appointments_status_index');
                $table->dropIndex('appointments_fae_id_status_index');
            });
        }

        if (Schema::hasTable('task_updates')) {
            Schema::table('task_updates', function (Blueprint $table) {
                $table->dropIndex('task_updates_task_id_created_at_index');
                $table->dropIndex('task_updates_author_role_created_at_index');
            });
        }

        if (Schema::hasTable('departments')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->dropIndex('departments_is_active_index');
            });
        }
    }
};