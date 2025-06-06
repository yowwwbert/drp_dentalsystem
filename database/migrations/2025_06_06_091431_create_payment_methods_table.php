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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->string('payment_method_id', 50)->primary();
            $table->string('payment_method_name')->comment('Name of the payment method');
            $table->string('payment_method_type', 50)->comment('Type of payment method (e.g., cash, card, online)');
            $table->text('description')->nullable()->comment('Description of the payment method');
            $table->boolean('is_active')->default(true)->comment('Indicates if the payment method is active');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
