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
        Schema::create('teeth', function (Blueprint $table) {
            $table->string('tooth_id', 50)->primary();
            $table->string('chart_id', 50)->comment('ID of the dental chart this tooth belongs to');
            $table->string('tooth_number', 10)->unique()->comment('Tooth number or identifier');
            $table->text('tooth_notes')->nullable()->comment('Additional notes about the tooth');
            $table->string('tooth_status', 50)->nullable()->comment('Status of the tooth');
            $table->string('created_by', 50)->nullable()->comment('ID of the user who created the tooth record');
            $table->string('updated_by', 50)->nullable()->comment('ID of the user who last updated the tooth record');
            $table->enum('status_type', ['treated_here', 'pre_existing', 'observed'])
                ->default('observed');
            $table->string('diagnosed_by')->nullable();

            $table->foreign('diagnosed_by')
                  ->references('dentist_id')
                  ->on('dentists')
                  ->onDelete('set null');
            
            
            $table->foreign('tooth_status')
                  ->references('tooth_mark_id')
                  ->on('tooth_marks')
                  ->onDelete('set null');
            
            $table->foreign('chart_id')
                  ->references('chart_id')
                  ->on('dental_charts')
                  ->onDelete('cascade');
            $table->foreign('created_by')
                    ->references('user_id')
                    ->on('users')
                    ->onDelete('cascade');
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
        Schema::dropIfExists('teeth');
    }
};
