<?php

namespace App\Models\Translations;

use A17\Twill\Models\Model;
use App\Models\Inquiry;

class InquiryTranslation extends Model
{
    protected $baseModuleModel = Inquiry::class;
}
