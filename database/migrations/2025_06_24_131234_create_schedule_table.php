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
        Schema::create('schedule', function (Blueprint $table) {
            $table->string('schedule_id', 36)->primary();
            $table->string('branch_id', 255);
            $table->date('schedule_date');
            $table->time('start_time');
            $table->time('end_time');

            $table->foreign('branch_id')
                ->references('branch_id')
                ->on('branch')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule');
    }
};
