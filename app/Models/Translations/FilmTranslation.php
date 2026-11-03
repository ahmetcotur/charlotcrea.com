<?php

namespace App\Models\Translations;

use A17\Twill\Models\Model;
use App\Models\Film;

class FilmTranslation extends Model
{
    protected $baseModuleModel = Film::class;
}
