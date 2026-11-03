<?php

namespace App\Models;

use A17\Twill\Models\Behaviors\HasMedias;
use A17\Twill\Models\Behaviors\HasPosition;
use A17\Twill\Models\Behaviors\HasRevisions;
use A17\Twill\Models\Behaviors\HasTranslation;
use A17\Twill\Models\Behaviors\Sortable;
use A17\Twill\Models\Model;

class Reference extends Model implements Sortable
{
    use HasMedias, HasPosition, HasRevisions, HasTranslation;

    protected $fillable = [
        'published',
        'title',
        'description',
        'position', 'import_key', 'website_url',
    ];

    public $mediasParams = ['logo' => ['default' => [['name' => 'default', 'ratio' => 0]]]];

    protected $casts = ['published' => 'boolean'];

    public $translatedAttributes = [
        'title',
        'description',
    ];

    public function getLogoThemeAttribute(): string
    {
        if ($this->import_key !== '/assets/references/th8palm.png') {
            return 'normal';
        }
        $original = 'reference-'.hash('sha256', $this->import_key).'.png';
        $logo = $this->medias->first(fn ($media) => $media->pivot->role === 'logo');

        return $logo?->uuid === $original ? 'inverse' : 'normal';
    }
}
