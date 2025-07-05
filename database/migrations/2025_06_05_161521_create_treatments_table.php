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
        Schema::create('treatments', function (Blueprint $table) {
            $table->string('treatment_id', 50)->primary();
            $table->string('treatment_name');
            $table->string('treatment_type', 50);
            $table->integer('treatment_duration')->nullable()->comment('Duration in minutes');
            $table->text('treatment_description')->nullable();
            $table->decimal('treatment_cost', 10, 2)->default(0.00);
            $table->boolean('is_active')->default(true)->comment('Indicates if the treatment is currently active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treatments');
    }
};
