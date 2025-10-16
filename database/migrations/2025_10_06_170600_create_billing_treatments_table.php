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
        Schema::create('billing_treatments', function (Blueprint $table) {
            $table->string('billing_id');
            $table->string('treatment_id');
            $table->string('dentist_id')->nullable();

            // Price before discount (for historical accuracy)
            $table->decimal('price', 10, 2)->nullable();

            // Optional per-treatment discount info
            $table->decimal('discount_amount', 10, 2)->nullable();
            $table->string('discount_reason')->nullable();

            $table->timestamps();

            // Foreign key constraints (for string-based IDs)
            $table->foreign('billing_id')->references('billing_id')->on('billings')->onDelete('cascade');
            $table->foreign('treatment_id')->references('treatment_id')->on('treatments')->onDelete('restrict');
            $table->foreign('dentist_id')->references('dentist_id')->on('dentists')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_treatments');
    }
};
