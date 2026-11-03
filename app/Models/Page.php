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
use App\Services\PageManagement;

class Page extends Model implements Sortable
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

    public function getManagementTitleAttribute(): string
    {
        return PageManagement::describe($this)['label'];
    }

    public function getManagementTypeAttribute(): string
    {
        return PageManagement::describe($this)['type'];
    }

    public function getTitleInBrowserAttribute(): string
    {
        return $this->ancestors->sortBy('_lft')->map(fn ($page) => $page->management_title)->push($this->management_title)->join(' / ');
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
