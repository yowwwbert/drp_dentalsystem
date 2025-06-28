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
        Schema::create('dentist_schedule', function (Blueprint $table) {
            $table->string('dentist_id', 36);
            $table->string('schedule_id', 36);
            $table->primary(['dentist_id', 'schedule_id']);
            $table->enum('status', ['Available', 'Not Available'])->default('Available');

            $table->foreign('dentist_id')
                ->references('dentist_id')
                ->on('dentists')
                ->onDelete('cascade');
            $table->foreign('schedule_id')
                ->references('schedule_id')
                ->on('schedules')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dentist_schedule');
    }
};
