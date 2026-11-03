<?php

namespace App\Models\Concerns;

use Illuminate\Validation\ValidationException;

trait ProtectsHierarchy
{
    public function getTitleInBrowserAttribute(): string
    {
        return $this->ancestors->sortBy('_lft')->map(fn ($item) => $item->title)->push($this->title)->filter()->join(' / ');
    }

    public function delete()
    {
        if ($this->children()->withTrashed()->exists()) {
            throw ValidationException::withMessages(['hierarchy' => 'Alt kayıtları olan bir üst kayıt silinemez. Önce alt kayıtları başka bir üst kayda taşıyın.']);
        }

        return parent::delete();
    }

    public static function saveTreeFromIds($nodeTree)
    {
        $seen = [];
        $flat = [];
        $visit = function ($nodes, $parent = null, $depth = 0) use (&$visit, &$seen, &$flat) {
            if ($depth > 8 || ! is_array($nodes)) {
                throw ValidationException::withMessages(['hierarchy' => 'Geçersiz hiyerarşi.']);
            }
            foreach ($nodes as $position => $node) {
                $id = (int) ($node['id'] ?? 0);
                if (! $id || isset($seen[$id]) || ! static::whereKey($id)->exists()) {
                    throw ValidationException::withMessages(['hierarchy' => 'Bir kayıt ağaçta yalnızca bir kez yer alabilir.']);
                }
                $seen[$id] = true;
                $flat[] = compact('id', 'parent', 'position');
                $visit($node['children'] ?? [], $id, $depth + 1);
            }
        };
        $visit($nodeTree);
        foreach ($flat as $node) {
            $model = static::findOrFail($node['id']);
            $model->parent_id = $node['parent'];
            $model->position = $node['position'];
            $model->save();
        }
    }
}
