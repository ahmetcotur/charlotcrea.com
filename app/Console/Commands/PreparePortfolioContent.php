<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Models\Project;
use App\Models\Reference;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PreparePortfolioContent extends Command
{
    protected $signature = 'charlot:prepare-portfolio-content';

    protected $description = 'Initialize bilingual portfolio SEO copy and known project brand without replacing edits';

    public function handle(): int
    {
        $content = json_decode(file_get_contents(base_path('data/portfolio-seo.json')), true, 512, JSON_THROW_ON_ERROR);
        DB::transaction(function () use ($content) {
            foreach ($content as $identity => $languages) {
                $page = Page::with('translations')->where('import_key', $identity)->first();
                if (! $page) {
                    continue;
                }
                foreach ($languages as $locale => $fields) {
                    $translation = $page->translate($locale);
                    if (! $translation || filled($translation->body) || ($page->source_data['portfolio_seo_initialized'][$locale] ?? false)) {
                        continue;
                    }
                    foreach (['photography' => '/en/photography/', 'film' => '/en/film/', 'contact' => '/en/contact/', 'services' => '/en/services/'] as $token => $key) {
                        $path = Page::where('import_key', $key)->first()?->translate($locale)?->path;
                        $fields['body'] = str_replace('{{'.$token.'}}', $path ?? '/'.$locale.'/', $fields['body']);
                    }
                    $translation->fill($fields)->save();
                    $source = $page->source_data ?? [];
                    $source['portfolio_seo_initialized'][$locale] = true;
                    $page->source_data = $source;
                }
                $page->save();
            }
            $brand = Reference::where('import_key', '/assets/references/25hours.png')->first();
            $project = Project::where('import_key', '/en/projects/25hours-dubai/')->first();
            if ($brand && $project && ! $project->reference_id) {
                $project->update(['reference_id' => $brand->id]);
            }
        });
        $this->info('Bilingual portfolio SEO and known project brand initialized. Existing edits preserved.');

        return self::SUCCESS;
    }
}
