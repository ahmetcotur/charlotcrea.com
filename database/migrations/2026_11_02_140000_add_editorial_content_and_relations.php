<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['pages', 'projects', 'films', 'destinations', 'posts'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->timestamp('published_at')->nullable();
                $table->boolean('relations_imported')->default(false);
            });
            Schema::table(rtrim($table, 's').'_translations', function (Blueprint $table) {
                $table->string('category')->nullable();
                $table->string('background')->nullable();
                $table->text('story_heading')->nullable();
                $table->text('story_intro')->nullable();
                $table->longText('faq_body')->nullable();
            });
        }
        Schema::table('destinations', fn (Blueprint $table) => $table->foreignId('parent_id')->nullable()->constrained('destinations')->nullOnDelete());
        Schema::table('films', fn (Blueprint $table) => $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete());
        foreach (['project', 'film'] as $type) {
            Schema::create('destination_'.$type, function (Blueprint $table) use ($type) {
                $table->foreignId('destination_id')->constrained()->cascadeOnDelete();
                $table->foreignId($type.'_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('position')->default(0);
                $table->unique(['destination_id', $type.'_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('destination_film');
        Schema::dropIfExists('destination_project');
        Schema::table('films', fn (Blueprint $table) => $table->dropConstrainedForeignId('project_id'));
        Schema::table('destinations', fn (Blueprint $table) => $table->dropConstrainedForeignId('parent_id'));
        foreach (['pages', 'projects', 'films', 'destinations', 'posts'] as $table) {
            Schema::table($table, fn (Blueprint $table) => $table->dropColumn(['published_at', 'relations_imported']));
            Schema::table(rtrim($table, 's').'_translations', fn (Blueprint $table) => $table->dropColumn(['category', 'background', 'story_heading', 'story_intro', 'faq_body']));
        }
    }
};
