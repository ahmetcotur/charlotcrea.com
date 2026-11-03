<?php

namespace App\Models;

use A17\Twill\Models\Behaviors\HasMedias;
use A17\Twill\Models\Behaviors\HasNesting;
use A17\Twill\Models\Behaviors\HasPosition;
use A17\Twill\Models\Behaviors\HasRevisions;
use A17\Twill\Models\Behaviors\HasTranslation;
use A17\Twill\Models\Behaviors\Sortable;
use A17\Twill\Models\Model;
use App\Models\Concerns\HasPublicContent;
use App\Models\Concerns\ProtectsHierarchy;

class Destination extends Model implements Sortable
{
    use HasMedias, HasPosition, HasRevisions, HasTranslation;
    use HasNesting, ProtectsHierarchy {
        ProtectsHierarchy::saveTreeFromIds insteadof \A17\Twill\Models\Behaviors\HasNesting;
    }
    use HasPublicContent;

    protected $fillable = [
        'published',
        'title',
        'description',
        'parent_id', 'published_at', 'relations_imported', 'position', 'import_key', 'country', 'city', 'video_url', 'source_data',
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

    public function projects()
    {
        return $this->belongsToMany(Project::class, $this->parent_id ? 'destination_project' : 'country_project')->withPivot('position')->orderByPivot('position');
    }

    public function films()
    {
        return $this->belongsToMany(Film::class, $this->parent_id ? 'destination_film' : 'country_film')->withPivot('position')->orderByPivot('position');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position');
    }
}
