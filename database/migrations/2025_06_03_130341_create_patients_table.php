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
        Schema::create('patients', function (Blueprint $table) {
            $table->string('patient_id', 50)->primary();
            $table->string('valid_id');
            $table->string('guardian_id')->nullable();
            $table->foreign('guardian_id')
                ->references('guardian_id')
                ->on('guardians')
                ->onDelete('set null');
            $table->integer('remaining_balance')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')
                ->references('user_id')
                ->on('users')
                ->onDelete('cascade');
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
