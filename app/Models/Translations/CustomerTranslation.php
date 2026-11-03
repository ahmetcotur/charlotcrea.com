<?php

namespace App\Models\Translations;

use A17\Twill\Models\Model;
use App\Models\Customer;

class CustomerTranslation extends Model
{
    protected $baseModuleModel = Customer::class;
}
