<?php

namespace App\Services;

use App\Models\Destination;

class PortfolioLocations
{
    public function initialize($model): void
    {
        $source = $model->source_data ?? [];
        if ($source['location_picker_initialized'] ?? false) {
            return;
        }
        $countries = $model->countries()->get()->mapWithKeys(fn ($country, $i) => [$country->id => ['position' => $i]]);
        foreach ($model->destinations()->get() as $destination) {
            $root = $destination;
            while ($root->parent) {
                $root = $root->parent;
            }
            $countries->put($root->id, ['position' => $countries->count()]);
        }
        $model->countries()->sync($countries->all());
        $rootIds = Destination::whereNull('parent_id')->pluck('id');
        $model->destinations()->detach($rootIds);
        $source['location_picker_initialized'] = true;
        $model->source_data = $source;
        $model->save();
    }

    public function syncLabels($model): void
    {
        $countries = $model->countries()->with('translations')->get();
        $locations = $model->locations()->with('translations')->get();
        $model->country = $countries->count() === 1 ? ($countries->first()->country ?: $countries->first()->translate('en')->title) : ($countries->isEmpty() ? null : 'International');
        $model->city = $locations->map(fn ($location) => $location->translate('en')->title)->join(' · ');
        $model->save();
    }
}
