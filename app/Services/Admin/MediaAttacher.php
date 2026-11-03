<?php

namespace App\Services\Admin;

use A17\Twill\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Attaches plain file uploads to Twill's media library (twill_medias +
 * twill_mediables) without going through Twill's own JS media picker, so
 * hand-rolled admin forms can still manage cover/gallery/logo images.
 */
class MediaAttacher
{
    /**
     * Replace the single image for a role (e.g. 'cover', 'logo') — detaches
     * whatever was attached before and attaches the new upload.
     */
    public function replace(Model $model, UploadedFile $file, string $role): void
    {
        $model->medias()->wherePivot('role', $role)->wherePivot('crop', 'default')->detach();
        $model->medias()->attach($this->store($file)->id, $this->pivot($role, $file));
    }

    /**
     * Append an image to a multi-image role (e.g. 'gallery'), ordered after
     * whatever is already attached.
     */
    public function append(Model $model, UploadedFile $file, string $role): void
    {
        $position = (int) $model->medias()->wherePivot('role', $role)->wherePivot('crop', 'default')->count();
        $model->medias()->attach($this->store($file)->id, [...$this->pivot($role, $file), 'position' => $position + 1]);
    }

    /** Detach one media item (by its twill_medias id) from the given role, and delete the underlying file/record. */
    public function remove(Model $model, int $mediaId, string $role): void
    {
        $model->medias()->wherePivot('role', $role)->wherePivot('crop', 'default')->detach($mediaId);

        $media = Media::find($mediaId);
        if ($media && $media->canDeleteSafely()) {
            Storage::disk(config('twill.media_library.disk'))->delete($media->uuid);
            $media->delete();
        }
    }

    public function reorder(Model $model, array $ids, string $role = 'gallery'): void
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $attached = $model->medias()->wherePivot('role', $role)->wherePivot('crop', 'default')->pluck(config('twill.medias_table', 'twill_medias').'.id')->all();
        if (array_diff($ids, $attached)) {
            throw ValidationException::withMessages(['gallery_order' => 'Galeri sıralaması yalnızca bu kaydın görsellerini içerebilir.']);
        }
        $ordered = array_merge($ids, array_values(array_diff($attached, $ids)));
        foreach ($ordered as $position => $id) {
            $model->medias()->wherePivot('role', $role)->wherePivot('crop', 'default')->updateExistingPivot($id, ['position' => $position + 1]);
        }
    }

    private function store(UploadedFile $file): Media
    {
        $uuid = Str::uuid().'/'.Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$file->getClientOriginalExtension();
        $file->storePubliclyAs(dirname($uuid), basename($uuid), config('twill.media_library.disk'));

        [$width, $height] = @getimagesize($file->getRealPath()) ?: [null, null];

        return Media::create([
            'uuid' => $uuid,
            'filename' => $file->getClientOriginalName(),
            'width' => $width,
            'height' => $height,
        ]);
    }

    private function pivot(string $role, UploadedFile $file): array
    {
        [$width, $height] = @getimagesize($file->getRealPath()) ?: [0, 0];

        return [
            'role' => $role,
            'crop' => 'default',
            'crop_x' => 0,
            'crop_y' => 0,
            'crop_w' => $width,
            'crop_h' => $height,
            'ratio' => 'default',
            'metadatas' => json_encode(['caption' => null, 'altText' => null, 'video' => null]),
        ];
    }
}
