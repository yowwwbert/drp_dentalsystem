<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->string('payment_id', 50)->unique()->nullable(false);
            $table->string('billing_id', 50)->nullable(false);
            $table->string('payment_method_id', 50)->nullable(false);
            $table->decimal('amount', 10, 2)->nullable(false);
            $table->dateTime('payment_date')->nullable(false);
            $table->string('notes', 255)->nullable();
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending')->nullable(false);
            $table->enum('payment_type', ['full', 'partial', 'advance'])->default('full')->nullable(false);
            $table->string('appointment_id', 50)->nullable();
            $table->string('patient_id', 50)->nullable();
            $table->string('handled_by', 50)->nullable(); // New column to track who handled the payment
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Foreign key constraints (assuming related tables exist)
            $table->foreign('patient_id')->references('patient_id')->on('patients')->onDelete('set null');
            $table->foreign('appointment_id')->references('appointment_id')->on('appointments')->onDelete('set null');
            $table->foreign('handled_by')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('billing_id')->references('billing_id')->on('billings')->onDelete('cascade');
            $table->foreign('payment_method_id')->references('payment_method_id')->on('payment_methods')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments');
    }
}