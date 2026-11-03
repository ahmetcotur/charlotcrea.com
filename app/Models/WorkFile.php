<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkFile extends Model
{
    protected $fillable = ['work_id', 'path', 'name', 'mime_type', 'size', 'released_at'];

    protected $hidden = ['path'];

    protected function casts(): array
    {
        return ['size' => 'integer', 'released_at' => 'datetime'];
    }

    public function work()
    {
        return $this->belongsTo(Work::class);
    }

    public function getSizeLabelAttribute(): string
    {
        return $this->size >= 1048576 ? number_format($this->size / 1048576, 1).' MB' : number_format($this->size / 1024, 1).' KB';
    }
}
