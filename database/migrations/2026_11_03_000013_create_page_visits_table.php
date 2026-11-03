<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_visits', function (Blueprint $table) {
            $table->id();
            $table->timestamp('visited_at')->index();
            $table->string('path', 1000);
            $table->string('locale', 2);
            $table->text('ip_address');
            $table->string('ip_hash', 64)->index();
            $table->string('visitor_hash', 64);
            $table->string('country_code', 2)->nullable();
            $table->string('device', 20);
            $table->string('browser', 30);
            $table->string('platform', 30);
            $table->string('referrer_host', 255)->nullable();
            $table->index(['visited_at', 'visitor_hash']);
            $table->index(['country_code', 'visited_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_visits');
    }
};
