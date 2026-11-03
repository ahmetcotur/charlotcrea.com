<?php

namespace App\Models;

use A17\Twill\Models\Behaviors\HasMedias;
use A17\Twill\Models\Behaviors\HasPosition;
use A17\Twill\Models\Behaviors\HasRevisions;
use A17\Twill\Models\Behaviors\HasTranslation;
use A17\Twill\Models\Behaviors\Sortable;
use A17\Twill\Models\Model;
use App\Models\Concerns\HasPublicContent;

class Film extends Model implements Sortable
{
    use HasMedias, HasPosition, HasRevisions, HasTranslation;
    use HasPublicContent;

    protected $fillable = [
        'published',
        'title',
        'description',
        'project_id', 'published_at', 'relations_imported', 'position', 'import_key', 'country', 'city', 'video_url', 'source_data',
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

    public function destinations()
    {
        return $this->belongsToMany(Destination::class, 'destination_film')->withPivot('position');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function driveId(): ?string
    {
        return preg_match('~^https://drive\.google\.com/file/d/([a-zA-Z0-9_-]+)/~', $this->video_url ?? '', $match) ? $match[1] : null;
    }

    public function countries()
    {
        return $this->belongsToMany(Destination::class, 'country_film', 'film_id', 'destination_id')->withPivot('position')->orderByPivot('position');
    }

    public function locations()
    {
        return $this->destinations()->whereNotNull('destinations.parent_id');
    }
}
