<?php

namespace App\Console\Commands;

use App\Models\Destination;
use App\Models\Page;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PrepareDestinationSeo extends Command
{
    protected $signature = 'charlot:prepare-destination-seo {--refresh : Reapply editorial copy after backing up current edits}';

    protected $description = 'Apply bilingual location-specific SEO copy and keyword plans, with a private backup';

    public function handle(): int
    {
        $copy = json_decode(file_get_contents(base_path('data/destination-seo-content.json')), true, 512, JSON_THROW_ON_ERROR);
        $changes = [];
        $backup = [];
        foreach (Destination::with('translations')->get() as $destination) {
            $identity = $destination->import_key ?: $destination->translate('en')?->path;
            if (! isset($copy[$identity])) {
                $this->warn('No editorial copy: '.$destination->title);

                continue;
            }
            if (! $this->option('refresh') && ($destination->source_data['destination_seo_version'] ?? null) === 1) {
                continue;
            }
            $changes[] = [$destination, $copy[$identity]];
            $backup[] = ['id' => $destination->id, 'source_data' => $destination->source_data, 'translations' => $destination->translations->toArray()];
        }
        $listing = Page::with('translations')->where('import_key', '/en/destinations/')->first();
        if ($listing && ($this->option('refresh') || ($listing->source_data['destination_listing_seo_version'] ?? null) !== 1)) {
            $backup[] = ['model' => 'page', 'id' => $listing->id, 'source_data' => $listing->source_data, 'translations' => $listing->translations->toArray()];
        }
        if ($backup) {
            $path = 'seo-backups/destinations-'.now()->format('Ymd-His').'-'.Str::random(6).'.json';
            if (! Storage::disk('local')->put($path, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))) {
                throw new \RuntimeException('Could not back up destination content. No changes applied.');
            }
            $this->info('Private content backup: '.$path);
        }
        DB::transaction(function () use ($changes) {
            foreach ($changes as [$destination, $languages]) {
                $source = $destination->source_data ?? [];
                foreach ($languages as $locale => $fields) {
                    $translation = $destination->translate($locale);
                    if (! $translation) {
                        continue;
                    }
                    $source['seo_keywords'][$locale] = $fields['keywords'];
                    unset($fields['keywords']);
                    $fields['body'] = $this->links($fields['body'], $locale);
                    $translation->fill($fields)->save();
                }
                $source['destination_seo_version'] = 1;
                $destination->source_data = $source;
                $destination->save();
            }
            $this->listing();
        });
        $this->info('SEO copy applied to '.count($changes).' destinations in Turkish and English. Publication and paths preserved.');

        return self::SUCCESS;
    }

    private function links(string $html, string $locale): string
    {
        foreach (['photography' => '/en/photography/', 'film' => '/en/film/', 'quote' => '/en/request-a-quote/'] as $token => $key) {
            $path = Page::where('import_key', $key)->first()?->publicPath($locale) ?: Page::where('import_key', '/en/contact/')->first()?->publicPath($locale) ?: '/'.$locale.'/';
            $html = str_replace('{{'.$token.'}}', e($path), $html);
        }

        return $html;
    }

    private function listing(): void
    {
        $page = Page::with('translations')->where('import_key', '/en/destinations/')->first();
        if (! $page || (! $this->option('refresh') && ($page->source_data['destination_listing_seo_version'] ?? null) === 1)) {
            return;
        }
        $copy = [
            'tr' => ['seo_title' => 'Otel Fotoğrafçılığı ve Film Destinasyonları | Charlot Crea', 'description' => 'Türkiye, BAE, Mısır ve Suudi Arabistan’daki otel fotoğrafçılığı ve film çalışmalarını ülke ve şehre göre keşfedin. Projeniz için çekim teklifi isteyin.', 'heading' => "Farklı konumlar.\nOrtak bir görsel dil.", 'intro' => 'Türkiye, Birleşik Arap Emirlikleri, Mısır ve Suudi Arabistan’daki fotoğraf ve film seçkilerini ülke ve şehir sayfalarından inceleyin.', 'body' => '<h2>Konuma göre otel fotoğrafçılığı ve tanıtım filmi</h2><p>Her otelin, mekânın ve markanın görsel ihtiyacı farklıdır. Destinasyon sayfaları, aynı ülke veya şehirle ilişkili fotoğraf ve film çalışmalarını bir araya getirir. Ülke sayfasından alt konumlara geçebilir, projenize yakın örnekleri inceleyebilirsiniz.</p><h3>Bir seçkiden kendi projenizin brief’ine</h3><p>Yeni bir çekim için yalnızca konumu değil, hangi alanların anlatılacağını ve görsellerin nerede kullanılacağını da düşünün. Fotoğraf sayısı, ana film, kısa sosyal medya kurguları ve takvim gibi bilgiler üretim kapsamını netleştirir.</p><p><a href="{{photography}}">Fotoğraf portföyünü</a> ve <a href="{{film}}">film seçkisini</a> karşılaştırın; konumunuzu ve beklentilerinizi <a href="{{quote}}">teklif formunda</a> paylaşın.</p>'],
            'en' => ['seo_title' => 'Hotel Photography & Film Destinations | Charlot Crea', 'description' => 'Explore hotel photography and films in Türkiye, the UAE, Egypt and Saudi Arabia by country and city. Share your location to request a production quote.', 'heading' => "Different locations.\nA shared visual language.", 'intro' => 'Explore photography and film selections from Türkiye, the United Arab Emirates, Egypt and Saudi Arabia through their country and city pages.', 'body' => '<h2>Hotel photography and film, explored by location</h2><p>Every hotel, space and brand has different visual needs. Destination pages bring together photography and film linked to the same country or city. Begin with a country, explore its locations and review work relevant to your project.</p><h3>From a selection to your own project brief</h3><p>For a new shoot, consider not only the location but the spaces to feature and where the images will be used. The number of photographs, main film, shorter social versions and schedule all help define the production scope.</p><p>Compare the <a href="{{photography}}">photography portfolio</a> and <a href="{{film}}">film selection</a>, then share your location and requirements through the <a href="{{quote}}">quote form</a>.</p>'],
        ];
        foreach ($copy as $locale => $fields) {
            $fields['body'] = $this->links($fields['body'], $locale);
            $page->translate($locale)?->fill($fields)->save();
        }
        $page->source_data = array_replace($page->source_data ?? [], ['destination_listing_seo_version' => 1]);
        $page->save();
    }
}
