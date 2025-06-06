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
        Schema::create('schedules', function (Blueprint $table) {
            $table->string('schedule_id', 50)->primary();
            $table->time('start_time');
            $table->time('end_time');
            $table->date('date');
            $table->foreign('branch_id')
                  ->references('branch_id')
                  ->on('branches')
                  ->onDelete('cascade');
            $table->string('branch_id', 50);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
