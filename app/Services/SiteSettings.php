<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

class SiteSettings
{
    private array $defaults;

    public function __construct()
    {
        $this->defaults = config('site');
    }

    public function defaults(): array
    {
        return $this->defaults + ['homepage_variant' => 'home-1', 'country_name' => 'Türkiye', 'whatsapp_number' => null, 'map_query' => null, 'map_url' => null, 'instagram_url' => null, 'vimeo_url' => null, 'linkedin_url' => null];
    }

    public function values(): array
    {
        $saved = Cache::remember('charlot:site-settings', 3600, fn () => SiteSetting::find(1)?->only((new SiteSetting)->getFillable()) ?? []);

        return array_replace($this->defaults(), $saved);
    }

    public function apply(): void
    {
        config(['site' => $this->values()]);
    }

    public function mapEmbedUrl(string $locale): ?string
    {
        $query = config('site.map_query') ?: config('site.address');

        return $query ? 'https://www.google.com/maps?'.http_build_query(['q' => $query, 'output' => 'embed', 'hl' => $locale], '', '&', PHP_QUERY_RFC3986) : null;
    }

    public function directionsUrl(): ?string
    {
        if ($custom = config('site.map_url')) {
            return $custom;
        }
        $query = config('site.map_query') ?: config('site.address');

        return $query ? 'https://www.google.com/maps/dir/?'.http_build_query(['api' => 1, 'destination' => $query], '', '&', PHP_QUERY_RFC3986) : null;
    }
}
