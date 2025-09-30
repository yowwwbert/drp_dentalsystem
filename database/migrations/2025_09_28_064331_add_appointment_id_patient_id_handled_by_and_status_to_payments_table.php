<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Add appointment_id to link directly to an appointment
            $table->string('appointment_id')->nullable()->after('billing_id');
            $table->foreign('appointment_id')->references('appointment_id')->on('appointments')->onDelete('set null');

            // Add patient_id for direct patient reference
            $table->string('patient_id')->nullable()->after('appointment_id');
            $table->foreign('patient_id')->references('patient_id')->on('patients')->onDelete('set null');

            // Add handled_by to track user who processed payment
            $table->string('handled_by')->nullable()->after('notes');
            $table->foreign('handled_by')->references('user_id')->on('users')->onDelete('set null');

            // Add status for payment lifecycle
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('completed')->after('amount');

            // Indexes for performance
            $table->index('appointment_id');
            $table->index('patient_id');
            $table->index('handled_by');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['appointment_id']);
            $table->dropForeign(['patient_id']);
            $table->dropForeign(['handled_by']);
            $table->dropColumn(['appointment_id', 'patient_id', 'handled_by', 'status']);
        });
    }
};