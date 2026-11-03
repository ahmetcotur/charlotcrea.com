<?php

namespace App\Providers;

use App\Models\Quote;
use App\Models\Reference;
use App\Models\SiteSetting;
use App\Models\Translations\ReferenceTranslation;
use App\Models\Work;
use App\Observers\ContentObserver;
use App\Observers\QuoteObserver;
use App\Observers\WorkObserver;
use App\Services\ContentTypes;
use App\Services\SiteSettings;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SiteSettings::class);
    }

    public function boot(): void
    {
        SiteSetting::observe(ContentObserver::class);
        Quote::observe(QuoteObserver::class);
        Work::observe(WorkObserver::class);
        Reference::observe(ContentObserver::class);
        ReferenceTranslation::observe(ContentObserver::class);
        foreach (ContentTypes::MODELS as $model) {
            $model::observe(ContentObserver::class);
            $translation = 'App\\Models\\Translations\\'.class_basename($model).'Translation';
            $translation::observe(ContentObserver::class);
        }
    }
}
