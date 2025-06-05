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
        Schema::create('medical_information', function (Blueprint $table) {
            $table->string('medical_info_id', 50)->primary();
            $table->string('patient_id', 50);
            $table->foreign('patient_id')
                ->references('patient_id')
                ->on('patients')
                ->onDelete('cascade');

            $table->string('previous_dentist')->nullable();
            $table->string('last_dental_visit')->nullable();
            $table->string('physician_name')->nullable();
            $table->string('physician_address')->nullable();
            $table->integer('physician_contact')->nullable();
            $table->string('physician_specialty')->nullable();
            $table->boolean('under_medication')->default(false);
            $table->boolean('congenital_abnormalities')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_information');
    }
};
