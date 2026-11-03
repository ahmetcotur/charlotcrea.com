<?php

namespace App\Observers;

use App\Models\LegacyRedirect;
use App\Models\SiteSetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ContentObserver
{
    public function saved(Model $model): void
    {
        if (str_ends_with(class_basename($model), 'Translation') && $model->wasChanged('path')) {
            $old = $model->getOriginal('path');
            if ($old && $model->path && $old !== $model->path) {
                LegacyRedirect::updateOrCreate(['source' => $old], ['target' => $model->path]);
            }
        }
        if ($model instanceof SiteSetting) {
            Cache::forget('charlot:site-settings');
        }
        $this->invalidate();
    }

    public function deleted(Model $model): void
    {
        $this->invalidate();
    }

    public function restored(Model $model): void
    {
        $this->invalidate();
    }

    private function invalidate(): void
    {
        Cache::forever('charlot:content-version', bin2hex(random_bytes(8)));
    }
}
