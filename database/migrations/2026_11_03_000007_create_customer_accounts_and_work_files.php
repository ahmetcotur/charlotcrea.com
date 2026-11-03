<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('enabled')->default(true);
            $table->timestamp('activated_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('customer_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('work_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_id')->constrained()->cascadeOnDelete();
            $table->string('path')->unique();
            $table->string('name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size');
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_files');
        Schema::dropIfExists('customer_password_reset_tokens');
        Schema::dropIfExists('customer_accounts');
    }
};
