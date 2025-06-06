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
        Schema::create('billings', function (Blueprint $table) {
            $table->string('billing_id', 50)->primary();
            $table->string('patient_id', 50)->comment('ID of the patient being billed');
            $table->decimal('amount', 10, 2)->comment('Total amount to be billed');
            $table->date('billing_date')->comment('Date of the billing');
            $table->integer('discount_amount')->default(0)->comment('Discount amount applied to the billing');
            $table->string('discount_reason')->nullable()->comment('Reason for the discount applied to the billing');
            $table->enum('status', ['Pending', 'Fully Paid', 'Partially Paid'])->default('Pending')->comment('Status of the billing (Pending, Fully Paid, Partially Paid)');

            $table->foreign('patient_id')
                  ->references('patient_id')
                  ->on('patients')
                  ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billings');
    }
};
