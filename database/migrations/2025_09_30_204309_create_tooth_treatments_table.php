<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tooth_treatments', function (Blueprint $table) {
            $table->string('treatment_id', 50)->primary();
            $table->string('tooth_id', 50);
            $table->string('surface_id', 50)->nullable()->comment('Optional: specific surface treated');
            $table->string('tooth_mark_id', 50)->comment('Type of treatment/mark applied');
            $table->string('dentist_id', 50)->comment('ID of the dentist who performed the treatment');
            $table->string('appointment_id', 50)->nullable()->comment('Optional: link to appointment');
            $table->dateTime('treatment_date')->comment('Date and time of the treatment');
            $table->text('treatment_notes')->nullable()->comment('Additional notes about the treatment');
            $table->timestamps();

            $table->foreign('tooth_id')
                ->references('tooth_id')
                ->on('teeth')
                ->onDelete('cascade');
            $table->foreign('surface_id')
                ->references('surface_id')
                ->on('tooth_surfaces')
                ->onDelete('set null');
            $table->foreign('tooth_mark_id')
                ->references('tooth_mark_id')
                ->on('tooth_marks')
                ->onDelete('restrict');
            $table->foreign('dentist_id')
                ->references('user_id')
                ->on('users')
                ->onDelete('restrict');
            $table->foreign('appointment_id')
                ->references('appointment_id')
                ->on('appointments')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tooth_treatments');
    }
};