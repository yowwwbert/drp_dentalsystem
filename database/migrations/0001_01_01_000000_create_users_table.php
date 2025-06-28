<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->string('user_id', 50)->primary();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->enum('suffix', ['Jr.', 'Sr.', 'III', 'IV', 'V'])->nullable();
            $table->integer('age')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('religion')->nullable();
            $table->string('sex')->nullable();
            $table->string('occupation')->nullable();
            $table->string('email_address')->unique();
            $table->string('phone_number')->nullable();
            $table->string('address')->nullable();
            $table->enum('user_type', ['Owner', 'Dentist', 'Staff', 'Patient'])->default('Patient');
            $table->string('status')->default('Active');
            $table->string('profile_picture')->nullable()->default('default.jpg');
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email_address')->primary();
            $table->foreign('email_address')->references('email_address')->on('users')->onDelete('cascade');
            $table->string('token');
            $table->timestamp('created_at');
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('user_id', 50)->nullable()->index();
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};