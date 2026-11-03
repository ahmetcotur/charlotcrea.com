<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('inquiries', function (Blueprint $table) {
            createDefaultTableFields($table);
            $table->string('name', 200);
            $table->string('email', 255)->index();
            $table->string('property')->nullable();
            $table->string('country', 120)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('service', 120)->nullable();
            $table->string('timing', 160)->nullable();
            $table->text('details');
            $table->string('locale', 5)->default('en');
            $table->string('status', 24)->default('new');
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->text('internal_notes')->nullable();
            $table->timestamp('received_at')->useCurrent();
        });

        Schema::create('inquiry_revisions', function (Blueprint $table) {
            createDefaultRevisionsTableFields($table, 'inquiry');
        });
    }

    public function down()
    {
        Schema::dropIfExists('inquiry_revisions');
        Schema::dropIfExists('inquiries');
    }
};
