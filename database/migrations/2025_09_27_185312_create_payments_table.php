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
            $table->integer('session_number')->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Foreign key constraints (assuming related tables exist)
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