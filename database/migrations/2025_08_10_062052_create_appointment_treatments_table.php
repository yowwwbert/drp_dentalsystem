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
        Schema::create('appointment_treatments', function (Blueprint $table) {
            $table->primary(['appointment_id', 'treatment_id']);
            $table->string('appointment_id');
            $table->string('treatment_id');
            $table->foreign('appointment_id')->references('appointment_id')->on('appointments')->onDelete('cascade');
            $table->foreign('treatment_id')->references('treatment_id')->on('treatments')->onDelete('cascade');

        });
    
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_treatments');
    }
};
