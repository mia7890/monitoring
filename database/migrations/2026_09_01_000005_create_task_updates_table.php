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
        if (!Schema::hasTable('task_updates')) {
            Schema::create('task_updates', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('task_id')->index();
                $table->unsignedInteger('fae_id')->nullable()->index();
                $table->string('author_role', 50)->default('fae');
                $table->string('author_name');
                $table->text('message');
                $table->string('attachment')->nullable();
                $table->integer('progress_at_update')->nullable();
                $table->string('status_at_update', 50)->nullable();
                $table->timestamps();

                $table->foreign('task_id')
                    ->references('id')
                    ->on('tasks')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');

                $table->foreign('fae_id')
                    ->references('id')
                    ->on('fae_users')
                    ->onDelete('set null')
                    ->onUpdate('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_updates');
    }
};
