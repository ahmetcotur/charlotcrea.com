<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\Destination;
use App\Services\PortfolioLocations;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared by the Project and Film admin controllers: both tag content with
 * Destinations (checkbox tree of countries + nested points), then rely on
 * the existing App\Services\PortfolioLocations::syncLabels() to recompute
 * the denormalized `country`/`city` display strings, exactly as the Twill
 * portfolio-location picker already does.
 */
trait TagsDestinations
{
    /**
     * @param  Model&object{countries(): \Illuminate\Database\Eloquent\Relations\BelongsToMany, destinations(): \Illuminate\Database\Eloquent\Relations\BelongsToMany}  $model
     * @param  array<int|string>  $selectedIds  Destination ids checked in the form (a mix of countries and points).
     */
    protected function syncDestinationTags(Model $model, array $selectedIds): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $selectedIds))));
        $destinations = Destination::whereIn('id', $ids)->get();

        $countryIds = [];
        $pointIds = [];

        foreach ($destinations as $destination) {
            if ($destination->parent_id === null) {
                $countryIds[] = $destination->id;

                continue;
            }

            $pointIds[] = $destination->id;
            $root = $destination->ancestors()->whereNull('parent_id')->first();
            $countryIds[] = $root?->id ?? $destination->id;
        }

        $model->countries()->sync(array_unique($countryIds));
        $model->destinations()->sync(array_unique($pointIds));

        app(PortfolioLocations::class)->syncLabels($model);
    }
}
