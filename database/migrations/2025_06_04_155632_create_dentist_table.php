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
        Schema::create('dentists', function (Blueprint $table) {
            $table->string('dentist_id', 50)->primary();
            $table->foreign('dentist_id')
                ->references('user_id')
                ->on('users')
                ->onDelete('cascade');
            $table->enum('dentist_type', ['Head Dentist', 'Dentist', 'Dental Assistant'])->default('Dentist');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dentist');
    }
};
