<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Services\HomeSections;
use App\Services\Import\LegacyHomeSections;
use App\Services\NativePageData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PrepareNativePages extends Command
{
    protected $signature = 'charlot:prepare-native-pages';

    protected $description = 'Snapshot existing section edits for native Blade pages without replacing content';

    public function handle(NativePageData $defaults): int
    {
        $pages = Page::with('translations')->whereIn('import_key', [...HomeSections::HOMES, '/en/about/', '/en/services/'])->get();
        $pending = $pages->filter(fn ($page) => ! ($page->source_data['native_blade_initialized'] ?? false));
        if ($pending->isNotEmpty()) {
            $backup = 'native-page-backups/'.now()->format('Ymd-His').'-'.Str::random(6).'.json';
            if (! Storage::disk('local')->put($backup, $pending->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                throw new \RuntimeException('Could not back up existing page content.');
            }
        }
        DB::transaction(function () use ($pending, $defaults) {
            foreach ($pending as $page) {
                $source = $page->source_data ?? [];
                if (in_array($page->import_key, HomeSections::HOMES)) {
                    $snapshot = app(LegacyHomeSections::class)->editor($page);
                    $source['native_home_sections'] = $snapshot ?: ($defaults->defaults($page)['sections'] ?? []);
                }
                $source['native_blade_initialized'] = true;
                $page->source_data = $source;
                $page->save();
            }
        });
        $this->info('Initialized '.$pending->count().' native Blade pages. Existing sections and content preserved.');

        return self::SUCCESS;
    }
}
