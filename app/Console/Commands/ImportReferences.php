<?php

namespace App\Console\Commands;

use A17\Twill\Models\Media;
use App\Models\Reference;
use DOMDocument;
use DOMXPath;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ImportReferences extends Command
{
    protected $signature = 'charlot:import-references';

    protected $description = 'Import existing brand logos and bilingual reference names, preserving CMS edits';

    public function handle(): int
    {
        $dom = new DOMDocument;
        $before = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.file_get_contents(base_path('dist/en/references/index.html')), LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($before);
        $xp = new DOMXPath($dom);
        $count = 0;
        foreach ($xp->query('//main//*[contains(concat(" ",normalize-space(@class)," ")," reference-grid ")]//img') as $img) {
            $src = $img->getAttribute('src');
            if (Reference::withTrashed()->where('import_key', $src)->exists()) {
                continue;
            }
            $original = realpath(base_path('dist/'.ltrim($src, '/')));
            if (! $original || ! str_starts_with($original, realpath(base_path('dist/assets')).DIRECTORY_SEPARATOR)) {
                $this->error('Missing logo: '.$src);

                return self::FAILURE;
            }
            $uuid = 'reference-'.hash('sha256', $src).'.'.pathinfo($src, PATHINFO_EXTENSION);
            $disk = Storage::disk(config('twill.media_library.disk'));
            $stream = fopen($original, 'rb');
            try {
                $disk->put($uuid, $stream);
            } finally {
                fclose($stream);
            }
            DB::transaction(function () use ($src, $img, $uuid, $original) {
                [$width, $height] = getimagesize($original);
                $media = Media::firstOrCreate(['uuid' => $uuid], ['filename' => basename($src), 'alt_text' => $img->getAttribute('alt'), 'width' => $width, 'height' => $height]);
                $model = new Reference(['published' => true, 'import_key' => $src]);
                foreach (['en', 'tr'] as $locale) {
                    $model->translateOrNew($locale)->fill(['title' => $img->getAttribute('alt'), 'active' => true]);
                }
                $model->save();
                $model->medias()->attach($media->id, ['role' => 'logo', 'crop' => 'default', 'ratio' => 'default', 'locale' => 'en', 'position' => 0, 'crop_x' => 0, 'crop_y' => 0, 'crop_w' => $width, 'crop_h' => $height, 'metadatas' => json_encode(['caption' => null, 'altText' => null, 'video' => null])]);
            });
            $count++;
        }
        $this->info("Imported $count references. Existing CMS edits were preserved.");

        return self::SUCCESS;
    }
}
