<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('destinations', function (Blueprint $table) {
            // this will create an id, a "published" column, and soft delete and timestamps columns
            createDefaultTableFields($table);
            $table->string('import_key')->nullable()->unique();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('video_url', 1000)->nullable();
            $table->json('source_data')->nullable();

            $table->integer('position')->unsigned()->nullable();

            // add those 2 columns to enable publication timeframe fields (you can use publish_start_date only if you don't need to provide the ability to specify an end date)
            // $table->timestamp('publish_start_date')->nullable();
            // $table->timestamp('publish_end_date')->nullable();
        });

        Schema::create('destination_translations', function (Blueprint $table) {
            createDefaultTranslationsTableFields($table, 'destination');
            $table->string('title', 200)->nullable();
            $table->text('description')->nullable();
            $table->string('path')->nullable()->unique();
            $table->string('seo_title')->nullable();
            $table->text('heading')->nullable();
            $table->text('intro')->nullable();
            $table->longText('body')->nullable();
            $table->longText('legacy_html')->nullable();
        });

        Schema::create('destination_revisions', function (Blueprint $table) {
            createDefaultRevisionsTableFields($table, 'destination');
        });
    }

    public function down()
    {
        Schema::dropIfExists('destination_revisions');
        Schema::dropIfExists('destination_translations');
        Schema::dropIfExists('destinations');
    }
};
