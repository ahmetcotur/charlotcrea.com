<?php

namespace App\Models;

use A17\Twill\Models\Behaviors\HasMedias;
use A17\Twill\Models\Behaviors\HasPosition;
use A17\Twill\Models\Behaviors\HasRevisions;
use A17\Twill\Models\Behaviors\HasTranslation;
use A17\Twill\Models\Behaviors\Sortable;
use A17\Twill\Models\Model;
use App\Models\Concerns\HasPublicContent;

class Post extends Model implements Sortable
{
    use HasMedias, HasPosition, HasRevisions, HasTranslation;
    use HasPublicContent;

    protected $fillable = [
        'published',
        'title',
        'description',
        'published_at', 'relations_imported', 'position', 'import_key', 'country', 'city', 'video_url', 'source_data',
        'path', 'seo_title', 'heading', 'intro', 'body',
        'category', 'background', 'story_heading', 'story_intro', 'faq_body',
    ];

    protected $casts = ['source_data' => 'array', 'published' => 'boolean', 'published_at' => 'datetime', 'relations_imported' => 'boolean'];

    public $mediasParams = [
        'cover' => ['default' => [['name' => 'default', 'ratio' => 0]]],
        'gallery' => ['default' => [['name' => 'default', 'ratio' => 0]]],
    ];

    public $translatedAttributes = [
        'path', 'seo_title', 'heading', 'intro', 'body',
        'category', 'background', 'story_heading', 'story_intro', 'faq_body',
        'title',
        'description',
    ];
}
