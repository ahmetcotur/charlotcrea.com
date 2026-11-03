<?php

namespace App\Console\Commands;

use A17\Twill\Models\Media;
use App\Services\ContentTypes;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportLegacyMedia extends Command
{
    protected $signature = 'charlot:import-media';

    protected $description = 'Copy original portfolio photographs into Twill without replacing edited selections';

    public function handle(): int
    {
        $count = 0;
        foreach (ContentTypes::MODELS as $class) {
            foreach ($class::with('medias')->cursor() as $model) {
                $source = $model->source_data ?? [];
                $roles = [
                    'cover' => array_filter([$source['cover'] ?? $source['poster'] ?? $source['image'] ?? null]),
                    'gallery' => array_column($source['images'] ?? [], 'src'),
                ];
                foreach ($roles as $role => $paths) {
                    // Emptying or reordering an imported gallery in CMS must survive reruns.
                    $marker = 'media_imported_'.$role;
                    if ($source[$marker] ?? false) {
                        continue;
                    }
                    if ($model->medias->contains(fn ($media) => $media->pivot->role === $role)) {
                        continue;
                    }
                    foreach (array_values($paths) as $position => $path) {
                        $original = realpath(base_path('dist/'.ltrim($path, '/')));
                        $assetRoot = realpath(base_path('dist/assets')).DIRECTORY_SEPARATOR;
                        if (! $original || ! str_starts_with($original, $assetRoot)) {
                            $this->error("Missing or invalid image: $path");

                            return self::FAILURE;
                        }
                        $uuid = 'legacy-'.hash('sha256', $path).'.'.pathinfo($path, PATHINFO_EXTENSION);
                        $disk = Storage::disk(config('twill.media_library.disk'));
                        if (! $disk->exists($uuid)) {
                            $stream = fopen($original, 'rb');
                            try {
                                $disk->put($uuid, $stream);
                            } finally {
                                fclose($stream);
                            }
                        }
                        [$width, $height] = getimagesize($original);
                        $imageInfo = collect($source['images'] ?? [])->firstWhere('src', $path) ?? [];
                        $alt = is_array($imageInfo['alt'] ?? null) ? ($imageInfo['alt']['en'] ?? $source['name']) : ($imageInfo['alt'] ?? $source['name'] ?? $model->title);
                        $media = Media::firstOrCreate(['uuid' => $uuid], [
                            'filename' => basename($path), 'alt_text' => $alt,
                            'width' => $width, 'height' => $height,
                        ]);
                        $model->medias()->attach($media->id, [
                            'role' => $role, 'crop' => 'default', 'ratio' => 'default', 'locale' => 'en',
                            'crop_x' => 0, 'crop_y' => 0, 'crop_w' => $width, 'crop_h' => $height,
                            'position' => $position, 'metadatas' => json_encode(['caption' => $imageInfo['caption'] ?? null, 'altText' => $imageInfo['alt'] ?? null, 'video' => null]),
                        ]);
                        $count++;
                    }
                    if ($paths) {
                        $source[$marker] = true;
                    }
                }
                $model->source_data = $source;
                if ($model->isDirty()) {
                    $model->save();
                }
            }
        }
        $this->info("Attached $count images. Existing CMS selections were preserved.");

        return self::SUCCESS;
    }
}
