<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    // This table holds one shared settings record, independently of database sequences.
    protected $attributes = ['id' => 1];

    protected $fillable = ['homepage_variant', 'name', 'email', 'telephone', 'whatsapp_number', 'street_address', 'district', 'city', 'country', 'country_name', 'address', 'map_query', 'map_url', 'instagram_url', 'vimeo_url', 'linkedin_url'];
}
