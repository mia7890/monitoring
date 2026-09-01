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
        if (!Schema::hasTable('admin_events')) {
            Schema::create('admin_events', function (Blueprint $table) {
                $table->increments('id');
                $table->string('title');
                $table->date('event_date')->index();
                $table->text('description')->nullable();
                $table->enum('category', ['meeting', 'busy', 'reminder', 'other'])->default('other');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_events');
    }
};
