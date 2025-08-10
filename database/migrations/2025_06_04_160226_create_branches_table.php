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
        Schema::create('branches', function (Blueprint $table) {
            $table->string('branch_id', 50)->primary();
            $table->string('branch_name')->unique();
            $table->string('branch_address')->nullable();
            $table->string('branch_contact')->nullable();
            $table->string('branch_email')->unique()->nullable();
            $table->string('branch_logo')->nullable();
            $table->string('branch_image')->nullable();
            $table->string('branch_map')->nullable();
            $table->string('branch_facebook')->nullable();
            $table->string('branch_instagram')->nullable();
            $table->json('operating_days')->nullable(); // Changed to json
            $table->time('opening_time')->nullable();
            $table->time('closing_time')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
