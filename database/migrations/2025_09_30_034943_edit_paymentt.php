<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            // Add foreign key constraint to existing handled_by column
            $table->foreign('handled_by')
                  ->references('user_id')
                  ->on('users')
                  ->onDelete('restrict'); // Prevent deletion of users referenced by payments
        });
    }

    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            // Only drop the foreign key constraint
            $table->dropForeign(['handled_by']);
        });
    }
};