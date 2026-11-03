<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegacyRedirect extends Model
{
    protected $fillable = ['source', 'target'];
}
