<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Services\HomeSections;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PrepareHomeSeo extends Command
{
    protected $signature = 'charlot:prepare-home-seo';

    protected $description = 'Replace homepage concept metadata with bilingual brand copy once, preserving later CMS edits';

    public function handle(): int
    {
        $copy = json_decode(file_get_contents(base_path('data/home-seo-content.json')), true, 512, JSON_THROW_ON_ERROR);
        $pages = Page::with('translations')->whereIn('import_key', HomeSections::HOMES)->get()
            ->filter(fn ($page) => ! ($page->source_data['home_seo_version'] ?? false));

        if ($pages->isNotEmpty()) {
            $backup = 'seo-backups/homepages-'.now()->format('Ymd-His').'-'.Str::random(6).'.json';
            if (! Storage::disk('local')->put($backup, $pages->values()->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                throw new \RuntimeException('Could not back up homepage content. No changes applied.');
            }
            $this->info('Private content backup: '.$backup);
        }

        DB::transaction(function () use ($pages, $copy) {
            foreach ($pages as $page) {
                foreach ($copy[$page->import_key] as $locale => $fields) {
                    $translation = $page->translate($locale);
                    if (! $translation) {
                        continue;
                    }
                    // Replace only empty or original imported metadata; keep editorial changes.
                    foreach ($fields as $field => $value) {
                        $originals = $locale === 'en'
                            ? ['Photography & Film | Charlot Crea', 'Photography and commercial film by Charlot Crea. Explore selected projects, international work and more than 25 years of photographic experience.']
                            : ['Fotoğraf & Film | Charlot Crea', 'Charlot Crea ile fotoğraf ve tanıtım filmi. Seçili projeleri, uluslararası çalışmaları ve 25 yılı aşkın fotoğraf deneyimini keşfedin.'];
                        if (blank($translation->$field) || in_array($translation->$field, $originals, true)
                            || str_contains($translation->$field, 'homepage concept')
                            || str_contains($translation->$field, 'ana sayfa konsepti')
                            || ($field === 'seo_title' && preg_match('/Home [23]|Ana Sayfa [23]/u', $translation->$field))) {
                            $translation->$field = $value;
                        }
                    }
                    $translation->save();
                }
                $page->source_data = array_replace($page->source_data ?? [], ['home_seo_version' => 1]);
                $page->save();
            }
        });
        $this->info('Homepage SEO prepared for '.$pages->count().' pages. Existing layouts and later CMS edits preserved.');

        return self::SUCCESS;
    }
}
