<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            createDefaultTableFields($table);
            $table->string('title', 200);
            $table->string('company_name')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone', 50)->nullable();
            $table->string('country', 100)->nullable();
            $table->text('notes')->nullable();
        });

        Schema::create('customer_revisions', function (Blueprint $table) {
            createDefaultRevisionsTableFields($table, 'customer');
        });
    }

    public function down()
    {
        Schema::dropIfExists('customer_revisions');
        Schema::dropIfExists('customers');
    }
};
