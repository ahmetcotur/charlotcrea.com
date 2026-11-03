<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('references', function (Blueprint $table) {
            // this will create an id, a "published" column, and soft delete and timestamps columns
            createDefaultTableFields($table);
            $table->string('import_key')->nullable()->unique();
            $table->string('website_url', 1000)->nullable();

            $table->integer('position')->unsigned()->nullable();

            // add those 2 columns to enable publication timeframe fields (you can use publish_start_date only if you don't need to provide the ability to specify an end date)
            // $table->timestamp('publish_start_date')->nullable();
            // $table->timestamp('publish_end_date')->nullable();
        });

        Schema::create('reference_translations', function (Blueprint $table) {
            createDefaultTranslationsTableFields($table, 'reference');
            $table->string('title', 200)->nullable();
            $table->text('description')->nullable();
        });

        Schema::create('reference_revisions', function (Blueprint $table) {
            createDefaultRevisionsTableFields($table, 'reference');
        });
    }

    public function down()
    {
        Schema::dropIfExists('reference_revisions');
        Schema::dropIfExists('reference_translations');
        Schema::dropIfExists('references');
    }
};
