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
        if (!Schema::hasTable('appointments')) {
            Schema::create('appointments', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('fae_id')->nullable();
                $table->string('user_name', 150);
                $table->date('appointment_date')->index();
                $table->text('reason');
                $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
                $table->text('admin_comment')->nullable();
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
        Schema::dropIfExists('appointments');
    }
};
