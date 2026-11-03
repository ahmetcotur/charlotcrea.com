<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('works', function (Blueprint $table) {
            $table->timestamp('delivered_at')->nullable();
            $table->text('delivery_note')->nullable();
        });
    }

    public function down()
    {
        Schema::table('works', function (Blueprint $table) {
            $table->dropColumn(['delivered_at', 'delivery_note']);
        });
    }
};
