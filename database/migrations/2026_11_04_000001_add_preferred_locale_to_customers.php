<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', fn (Blueprint $table) => $table->string('preferred_locale', 2)->default('tr'));
        DB::table('customers')->orderBy('id')->chunkById(200, function ($customers) {
            foreach ($customers as $customer) {
                $locale = DB::table('inquiries')->where('customer_id', $customer->id)->orderByDesc('received_at')->orderByDesc('id')->value('locale');
                if (in_array($locale, ['tr', 'en'], true)) {
                    DB::table('customers')->where('id', $customer->id)->update(['preferred_locale' => $locale]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', fn (Blueprint $table) => $table->dropColumn('preferred_locale'));
    }
};
