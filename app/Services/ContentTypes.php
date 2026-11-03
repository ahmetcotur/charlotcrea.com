<?php

namespace App\Services;

use App\Models\Destination;
use App\Models\Film;
use App\Models\Page;
use App\Models\Post;
use App\Models\Project;

class ContentTypes
{
    public const MODELS = [Page::class, Project::class, Film::class, Destination::class, Post::class];
}
