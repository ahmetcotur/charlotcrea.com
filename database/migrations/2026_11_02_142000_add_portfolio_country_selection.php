<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['project', 'film'] as $type) {
            Schema::create('country_'.$type, function (Blueprint $table) use ($type) {
                $table->foreignId('destination_id')->constrained()->cascadeOnDelete();
                $table->foreignId($type.'_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('position')->default(0);
                $table->unique(['destination_id', $type.'_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('country_project');
        Schema::dropIfExists('country_film');
    }
};
