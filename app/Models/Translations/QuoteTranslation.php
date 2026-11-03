<?php

namespace App\Models\Translations;

use A17\Twill\Models\Model;
use App\Models\Quote;

class QuoteTranslation extends Model
{
    protected $baseModuleModel = Quote::class;
}
