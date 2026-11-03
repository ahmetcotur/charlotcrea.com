<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Work;
use App\Models\WorkDeliveryLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkDeliveryLinkController extends Controller
{
    public function store(Request $request, Work $work)
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($work, $data) {
            Work::whereKey($work->id)->lockForUpdate()->firstOrFail();
            $work->deliveryLinks()->create($data);
        });

        return redirect()->route('admin.works.edit', ['work' => $work->id, 'tab' => 'delivery'])->with('success', 'Google Drive bağlantısı eklendi. Müşteriye açmak için Teslim et düğmesini kullanın.');
    }

    public function update(Request $request, Work $work, WorkDeliveryLink $link)
    {
        abort_unless($link->work_id === $work->id, 404);
        $data = $this->validated($request);
        DB::transaction(function () use ($work, $link, $data) {
            Work::whereKey($work->id)->lockForUpdate()->firstOrFail();
            $link->fill($data);
            if ($link->isDirty(['title', 'url'])) {
                $link->released_at = null;
            }
            $link->save();
        });

        return redirect()->route('admin.works.edit', ['work' => $work->id, 'tab' => 'delivery'])->with('success', 'Bağlantı güncellendi. Değişikliği müşteriye açmak için yeniden teslim edin.');
    }

    public function destroy(Work $work, WorkDeliveryLink $link)
    {
        abort_unless($link->work_id === $work->id, 404);
        $link->delete();

        return redirect()->route('admin.works.edit', ['work' => $work->id, 'tab' => 'delivery'])->with('success', 'Teslimat bağlantısı kaldırıldı. Drive üzerindeki dosyalar silinmedi.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'url' => ['required', 'string', 'max:2000', 'url:https', function ($attribute, $value, $fail) {
                $parts = parse_url($value);
                if (! is_array($parts) || ! in_array(strtolower($parts['host'] ?? ''), ['drive.google.com', 'docs.google.com'], true) || isset($parts['user']) || isset($parts['pass']) || (isset($parts['port']) && $parts['port'] !== 443)) {
                    $fail('Geçerli bir HTTPS Google Drive bağlantısı girin (drive.google.com veya docs.google.com).');
                }
            }],
        ]);
    }
}
