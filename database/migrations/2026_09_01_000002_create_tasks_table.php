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
        if (!Schema::hasTable('tasks')) {
            Schema::create('tasks', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('fae_id')->nullable();
                $table->string('region', 100)->nullable();
                $table->string('course')->nullable();
                $table->string('task_name');
                $table->text('description')->nullable();
                $table->date('deadline')->nullable();
                $table->enum('status', ['Pending', 'In Progress', 'Completed', 'Overdue'])->default('Pending');
                $table->unsignedTinyInteger('progress')->default(0);
                $table->enum('priority', ['Low', 'Medium', 'High', 'Urgent'])->default('Medium');
                $table->timestamps();

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
        Schema::dropIfExists('tasks');
    }
};
