<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', fn (Blueprint $table) => $table->string('demo_key')->nullable()->unique());
        Schema::table('quotes', fn (Blueprint $table) => $table->string('demo_key')->nullable()->unique());
    }

    public function down(): void
    {
        Schema::table('quotes', fn (Blueprint $table) => $table->dropUnique(['demo_key']));
        Schema::table('quotes', fn (Blueprint $table) => $table->dropColumn('demo_key'));
        Schema::table('customers', fn (Blueprint $table) => $table->dropUnique(['demo_key']));
        Schema::table('customers', fn (Blueprint $table) => $table->dropColumn('demo_key'));
    }
};
