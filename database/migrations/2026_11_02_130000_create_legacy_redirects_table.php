<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_redirects', function (Blueprint $table) {
            $table->id();
            $table->string('source')->unique();
            $table->string('target');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_redirects');
    }
};
