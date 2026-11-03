<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->string('type', 20)->default('contact')->index();
            $table->string('phone', 60)->nullable();
            $table->string('project_type', 120)->nullable();
            $table->string('budget', 120)->nullable();
            $table->string('usage', 1000)->nullable();
            $table->text('deliverables')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', fn (Blueprint $table) => $table->dropColumn(['type', 'phone', 'project_type', 'budget', 'usage', 'deliverables']));
    }
};
