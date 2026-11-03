<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\CustomerAccount;
use App\Models\Inquiry;
use App\Models\Quote;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SeedWorkflowDemos extends Command
{
    protected $signature = 'charlot:demo-workflows';

    protected $description = 'Add connected local demo enquiries, portal accounts, quotes, jobs and Drive deliveries';

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Workflow demos are available only in local/testing environments.');

            return self::FAILURE;
        }
        $disk = Storage::disk('local');
        $manifest = $disk->exists('demo-workflows.json') ? json_decode($disk->get('demo-workflows.json'), true, 512, JSON_THROW_ON_ERROR) : [];
        foreach ([
            'pending' => ['TR · Otel teklif onayı', 'Lara Horizon Hotel', 'sent', null, 'tr'],
            'planned' => ['EN · Architecture production', 'North Studio', 'accepted', 'planned', 'en'],
            'delivered' => ['TR · Resort teslimat onayı', 'Ege Bay Resort', 'accepted', 'in_progress', 'tr'],
            'revision' => ['EN · Film revision', 'Olive Brand', 'accepted', 'in_progress', 'en'],
            'completed' => ['TR · Tamamlanan kurumsal çekim', 'Kent Design', 'accepted', 'completed', 'tr'],
        ] as $key => [$label, $company, $status, $workStatus, $locale]) {
            if (Customer::withTrashed()->where('demo_key', 'workflow-v2-'.$key)->exists()) {
                continue;
            }
            $manifest[$key] = DB::transaction(function () use ($key, $label, $company, $status, $workStatus, $locale) {
                $email = 'workflow-'.$key.'@demo.example.test';
                $customer = Customer::create(['title' => '[DEMO] '.$company, 'company_name' => $company, 'email' => $email, 'country' => $locale === 'tr' ? 'Türkiye' : 'United Kingdom', 'preferred_locale' => $locale, 'demo_key' => 'workflow-v2-'.$key, 'notes' => '[DEMO] Yerel test senaryosu; gerçek müşteri değildir.']);
                $password = Str::password(20, symbols: false);
                CustomerAccount::create(['customer_id' => $customer->id, 'email' => $email, 'password' => $password, 'activated_at' => now(), 'enabled' => true]);
                foreach (['contact', 'quote'] as $type) {
                    Inquiry::create(['customer_id' => $customer->id, 'type' => $type, 'name' => '[DEMO] '.$company, 'email' => $email, 'locale' => $locale, 'property' => $company, 'country' => $customer->country, 'city' => 'Antalya', 'service' => 'Photography & film', 'timing' => 'Gelecek ay / next month', 'project_type' => 'Hotel / Architecture', 'budget' => '5.000–10.000 EUR', 'usage' => 'Website, social media, press', 'deliverables' => '30 retouched photographs, 60-second brand film', 'details' => '[DEMO] İki günlük çekim; mekân, mimari ve marka hikâyesi. Bu kayıt formdan gelecek verileri örnekler.', 'status' => $type === 'contact' ? 'new' : ($status === 'accepted' ? 'converted' : 'reviewing'), 'received_at' => now()->subDays(2)]);
                }
                $quote = Quote::create(['title' => '[DEMO] '.$label, 'customer_id' => $customer->id, 'published' => true, 'demo_key' => 'workflow-v2-'.$key, 'scope' => "İki günlük fotoğraf ve film prodüksiyonu.\n30 rötuşlu fotoğraf, 60 saniyelik film ve bir revizyon turu.\nTeslimatlar Google Drive üzerinden paylaşılır.", 'valid_until' => today()->addDays(21), 'currency' => 'EUR']);
                $quote->syncItems([
                    ['title' => 'Photography production', 'description' => '2 shooting days, 30 retouched images', 'quantity' => 2, 'unit_price' => 1400],
                    ['title' => 'Brand film', 'description' => '60-second film, edit and color grade', 'quantity' => 1, 'unit_price' => 2200],
                ]);
                $quote->update(['status' => 'sent']);
                if ($status === 'accepted') {
                    $quote->update(['status' => 'accepted']);
                }
                $work = $quote->works()->first();
                if ($work) {
                    $delivered = in_array($key, ['delivered', 'revision', 'completed']);
                    $work->update(['demo_key' => 'workflow-v2-'.$key, 'status' => $workStatus, 'starts_at' => $delivered ? today()->subDays(10) : today()->addDays(10), 'due_at' => $delivered ? today()->subDays(2) : today()->addDays(20), 'delivered_at' => $delivered ? now()->subDay() : null, 'delivery_note' => $delivered ? '[DEMO] Örnek teslimat. Drive bağlantıları yer tutucudur; gerçek dosya içermez.' : null]);
                    $work->deliveryLinks()->create(['title' => '[DEMO] Final fotoğraf ve film klasörü', 'url' => 'https://drive.google.com/drive/folders/DEMO-'.$key.'-placeholder', 'released_at' => $delivered ? now()->subDay() : null]);
                    if ($key === 'revision') {
                        $work->deliveryLinks()->create(['title' => '[DEMO] Revizyon 2 · henüz teslim edilmedi', 'url' => 'https://drive.google.com/drive/folders/DEMO-revision-draft-placeholder']);
                        $work->revisionRequests()->create(['status' => 'requested', 'customer_note' => '[DEMO] Film açılışını kısaltalım; son sahnede logoyu iki saniye daha tutalım.', 'requested_at' => now()]);
                    }
                    if (in_array($key, ['revision', 'completed'])) {
                        $work->revisionRequests()->create(['status' => 'approved', 'customer_note' => '[DEMO] İlk fotoğraf seçkisinin renkleri daha sıcak olsun.', 'admin_response' => '[DEMO] İstenen renk düzeltmesi tamamlandı.', 'requested_at' => now()->subDays(3), 'resolved_at' => now()->subDays(2)]);
                    }
                    $work->statusEvents()->update(['note' => '[DEMO] CLI ile oluşturulan iş akışı senaryosu.']);
                }
                $quote->statusEvents()->update(['note' => '[DEMO] CLI ile oluşturulan teklif akışı; gerçek müşteri işlemi değildir.']);

                return ['label' => $label, 'locale' => $locale, 'email' => $email, 'password' => $password, 'customer_id' => $customer->id, 'quote_id' => $quote->id, 'work_id' => $work?->id];
            });
        }
        $disk->put('demo-workflows.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        chmod($disk->path('demo-workflows.json'), 0600);
        $this->info('Demo workflows ready. Review /admin/demo-workflows. No emails sent; Drive links are placeholders.');

        return self::SUCCESS;
    }
}
