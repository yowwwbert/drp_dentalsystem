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
        Schema::create('tooth_surfaces', function (Blueprint $table) {
            $table->string('surface_id', 50)->primary();
            $table->string('tooth_id', 50);
            $table->enum('surface_type', ['Mesial', 'Distal', 'Buccal', 'Lingual', 'Occlusal']);
            $table->string('surface_status', 50)->nullable();
            $table->string('created_by', 50)->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->text('surface_notes')->nullable();

            $table->foreign('tooth_id')
                ->references('tooth_id')
                ->on('teeth')
                ->onDelete('cascade');
            $table->foreign('surface_status')
                ->references('tooth_mark_id')
                ->on('tooth_marks')
                ->onDelete('set null');
            $table->foreign('created_by')
                ->references('user_id')
                ->on('users')
                ->onDelete('set null');
            $table->foreign('updated_by')
                ->references('user_id')     
                ->on('users')
                ->onDelete('set null'); 
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tooth_surfaces');
    }
};
