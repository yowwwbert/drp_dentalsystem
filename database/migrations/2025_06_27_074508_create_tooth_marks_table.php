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
        Schema::create('tooth_marks', function (Blueprint $table) {
            $table->string('tooth_mark_id', 50)->primary()->comment('Custom ID for the tooth mark');
            $table->string('mark_name', 100)->comment('Name of the mark, e.g., Cavity, Filling');
            $table->string('mark_color', 7)->default('#000000')->comment('Hex color code for visualization, e.g., #FF0000');
            $table->string('created_by', 50)->nullable()->comment('ID of the user who created the mark');
            
            $table->foreign('created_by')
                ->references('user_id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tooth_marks');
    }
};
