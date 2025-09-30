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
        Schema::table('payments', function (Blueprint $table) {
            // Remove session_number column if it exists
            if (Schema::hasColumn('payments', 'session_number')) {
                $table->dropColumn('session_number');
            }
            // Add payment_type column
            $table->enum('payment_type', ['Partial', 'Full'])->default('Full')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Restore session_number column
            $table->integer('session_number')->default(1)->after('payment_date');
            // Remove payment_type column
            $table->dropColumn('payment_type');
        });
    }
};