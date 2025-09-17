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
        Schema::create('appointments', function (Blueprint $table) {
            $table->string('appointment_id', 50)->primary();
            $table->string('patient_id', 50)->comment('ID of the patient');
            $table->string('dentist_id', 50)->comment('ID of the dentist');
            $table->string('schedule_id', 50)->comment('ID of the schedule');
            $table->string('branch_id', 50)->comment('ID of the branch');
            $table->string('billing_id', 50)->nullable()->comment('ID of the billing record, if applicable');
            $table->enum('status', ['Scheduled', 'Checked In', 'Completed', 'Cancelled', 'No Show'])->default('Scheduled')->comment('Status of the appointment');
            $table->text('notes')->nullable()->comment('Additional notes for the appointment');
            $table->string('status_changed_by', 50)->comment('ID of the user who changed the status of the appointment');
            $table->timestamp('status_changed_at')->nullable()->comment('Timestamp when the status was changed');
            $table->text('reason_for_status_change')->nullable()->comment('Reason for changing the status of the appointment');
            $table->string('appointment_created_by', 50)->comment('ID of the user who created the appointment');

            $table->foreign('patient_id')
                    ->references('patient_id')
                    ->on('patients')
                    ->onDelete('cascade');
            $table->foreign('dentist_id')
                    ->references('dentist_id')
                    ->on('dentists')
                    ->onDelete('cascade');
            $table->foreign('schedule_id')
                    ->references('schedule_id')
                    ->on('schedules')
                    ->onDelete('cascade');
            $table->foreign('branch_id')
                    ->references('branch_id')
                    ->on('branches')
                    ->onDelete('cascade');
            $table->foreign('billing_id')
                    ->references('billing_id')
                    ->on('billings')
                    ->onDelete('set null');
            $table->foreign('status_changed_by')
                    ->references('user_id')
                    ->on('users')
                    ->onDelete('cascade');
            $table->foreign('appointment_created_by')
                    ->references('user_id')
                    ->on('users')
                    ->onDelete('cascade');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
