<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            foreach (['name', 'email', 'telephone', 'whatsapp_number', 'street_address', 'district', 'city', 'country', 'country_name'] as $field) {
                $table->string($field)->nullable();
            }
            foreach (['address', 'map_query', 'map_url', 'instagram_url', 'vimeo_url', 'linkedin_url'] as $field) {
                $table->text($field)->nullable();
            }
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
