<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('works', function (Blueprint $table) {
            createDefaultTableFields($table);
            $table->string('title', 200);
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 24)->default('planned');
            $table->date('starts_at')->nullable();
            $table->date('due_at')->nullable();
            $table->text('brief')->nullable();
            $table->text('internal_notes')->nullable();
            $table->string('demo_key')->nullable()->unique();
        });

        Schema::create('work_revisions', function (Blueprint $table) {
            createDefaultRevisionsTableFields($table, 'work');
        });
    }

    public function down()
    {
        Schema::dropIfExists('work_revisions');
        Schema::dropIfExists('works');
    }
};
