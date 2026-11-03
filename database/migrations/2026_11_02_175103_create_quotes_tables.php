<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('quotes', function (Blueprint $table) {
            createDefaultTableFields($table);
            $table->string('title', 200);
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('quote_number', 40)->unique();
            $table->string('status', 24)->default('draft');
            $table->decimal('amount', 12, 2)->default(0);
            $table->char('currency', 3)->default('EUR');
            $table->date('valid_until')->nullable();
            $table->text('scope')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
        });

        Schema::create('quote_revisions', function (Blueprint $table) {
            createDefaultRevisionsTableFields($table, 'quote');
        });

        Schema::create('quote_status_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 24)->nullable();
            $table->string('to_status', 24);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('quote_revisions');
        Schema::dropIfExists('quote_status_events');
        Schema::dropIfExists('quotes');
    }
};
