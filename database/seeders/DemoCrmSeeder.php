<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Inquiry;
use App\Models\Quote;
use App\Models\Work;
use Illuminate\Database\Seeder;

class DemoCrmSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [];
        foreach ([
            'couple' => ['title' => '[ÖRNEK] Ada & Deniz Yılmaz', 'company_name' => 'Kişisel çekim', 'email' => 'ada.deniz@example.test', 'phone' => '+90 532 000 00 01', 'country' => 'Türkiye', 'notes' => 'Bodrum’da yaz düğünü. Fotoğraf ve kısa film talep ediyor.'],
            'hotel' => ['title' => '[ÖRNEK] Maison Lune Hotel', 'company_name' => 'Maison Lune Hospitality', 'email' => 'marketing@maisonlune.example.test', 'phone' => '+33 1 00 00 00 02', 'country' => 'France', 'notes' => 'Yeni sezon otel fotoğrafları ve marka hikâyesi filmi.'],
            'agency' => ['title' => '[ÖRNEK] Northline Creative', 'company_name' => 'Northline Creative Agency', 'email' => 'studio@northline.example.test', 'phone' => '+44 20 0000 0003', 'country' => 'United Kingdom', 'notes' => 'Suudi Arabistan’daki resort açılışı için prodüksiyon ortağı arıyor.'],
        ] as $key => $attributes) {
            $customer = Customer::withTrashed()->firstOrNew(['demo_key' => 'demo-customer-'.$key]);
            if ($customer->trashed()) {
                $customer->restore();
            }
            $customer->fill([...$attributes, 'demo_key' => 'demo-customer-'.$key]);
            $customer->published = true;
            $customer->save();
            $customers[$key] = $customer;
        }

        $prospect = Customer::withTrashed()->firstOrNew(['demo_key' => 'demo-customer-inquiry']);
        if (! $prospect->exists) {
            $prospect = Customer::withTrashed()->firstOrNew(['email' => 'sophie.laurent@example.test']);
        }
        if ($prospect->trashed()) {
            $prospect->restore();
        }
        $prospect->fill([
            'title' => '[ÖRNEK] Sophie Laurent',
            'company_name' => '[ÖRNEK] Bodrum sezon çekimi',
            'email' => 'sophie.laurent@example.test',
            'phone' => '+33 6 00 00 00 04',
            'country' => 'France',
            'notes' => 'Örnek web brief’i gönderdi; ilk görüşme planlanacak.',
            'demo_key' => 'demo-customer-inquiry',
            'published' => true,
        ])->save();
        $inquiry = Inquiry::firstOrCreate(
            ['email' => 'sophie.laurent@example.test', 'property' => '[ÖRNEK] Bodrum sezon çekimi', 'locale' => 'tr'],
            [
                'name' => '[ÖRNEK] Sophie Laurent',
                'country' => 'Other',
                'city' => 'Bodrum',
                'service' => 'Photography & film',
                'timing' => '2027 Haziran',
                'details' => '[ÖRNEK] Otel ve çevre fotoğrafları, kısa marka filmi.',
                'status' => 'new',
                'customer_id' => $prospect->id,
                'received_at' => now(),
                'published' => true,
            ]
        );
        $inquiry->published = true;
        $inquiry->save();

        $quotes = [];
        foreach ([
            'wedding' => ['customer' => 'couple', 'title' => '[ÖRNEK] Bodrum düğün fotoğrafı ve filmi', 'status' => 'sent', 'amount' => 4200, 'currency' => 'EUR', 'valid_until' => now()->addDays(20), 'scope' => 'İki günlük çekim; fotoğraf seçkisi, kısa highlight filmi ve çevrim içi galeri.', 'internal_notes' => 'Örnek senaryo: teklif gönderildi, müşteri yanıtı bekleniyor.'],
            'hotel' => ['customer' => 'hotel', 'title' => '[ÖRNEK] Maison Lune sezon prodüksiyonu', 'status' => 'accepted', 'amount' => 7800, 'currency' => 'EUR', 'valid_until' => now()->addDays(35), 'scope' => 'Otel ve çevre fotoğrafları, 60 saniyelik marka filmi, sosyal medya kesitleri.', 'internal_notes' => 'Örnek senaryo: teklif kabul edildi, prodüksiyon planlaması başladı.'],
            'resort' => ['customer' => 'agency', 'title' => '[ÖRNEK] Al Noor Resort açılış kampanyası', 'status' => 'draft', 'amount' => 12500, 'currency' => 'USD', 'valid_until' => now()->addDays(30), 'scope' => 'Mekân fotoğrafları, ekip portreleri ve kampanya filmi için kapsam taslağı.', 'internal_notes' => 'Örnek senaryo: kapsam ve bütçe müşteriyle paylaşılmadan önce hazırlanıyor.'],
        ] as $key => $attributes) {
            $quote = Quote::withTrashed()->firstOrNew(['demo_key' => 'demo-quote-'.$key]);
            if ($quote->trashed()) {
                $quote->restore();
            }
            $quote->fill([
                ...collect($attributes)->except('customer')->all(),
                'customer_id' => $customers[$attributes['customer']]->id,
                'quote_number' => 'CC-DEMO-'.strtoupper(substr($key, 0, 3)),
                'demo_key' => 'demo-quote-'.$key,
                'published' => $attributes['status'] !== 'draft',
            ]);
            $quote->save();
            $quotes[$key] = $quote;
        }

        $quotes['wedding']->syncItems([
            ['title' => '[ÖRNEK] Düğün günü fotoğraf çekimi (tam gün)', 'description' => '8 saat, 2 fotoğrafçı', 'quantity' => 1, 'unit_price' => 2600],
            ['title' => '[ÖRNEK] Kısa film (3-5 dk, drone dahil)', 'description' => null, 'quantity' => 1, 'unit_price' => 1200],
            ['title' => '[ÖRNEK] Online galeri ve dijital teslimat', 'description' => null, 'quantity' => 1, 'unit_price' => 400],
        ]);

        $quotes['hotel']->syncItems([
            ['title' => '[ÖRNEK] Otel ve çevre fotoğraf çekimi', 'description' => 'Günlük', 'quantity' => 2, 'unit_price' => 1800],
            ['title' => '[ÖRNEK] 60 saniyelik marka filmi', 'description' => null, 'quantity' => 1, 'unit_price' => 3200],
            ['title' => '[ÖRNEK] Sosyal medya kesitleri', 'description' => 'Dikey format, adet bazlı', 'quantity' => 5, 'unit_price' => 200],
        ]);

        $quotes['resort']->syncItems([
            ['title' => '[ÖRNEK] Mekân fotoğrafları', 'description' => 'Günlük', 'quantity' => 3, 'unit_price' => 1500],
            ['title' => '[ÖRNEK] Ekip portreleri (yarım gün)', 'description' => null, 'quantity' => 1, 'unit_price' => 2000],
            ['title' => '[ÖRNEK] Kampanya filmi (60-90 sn)', 'description' => null, 'quantity' => 1, 'unit_price' => 6000],
        ]);

        $works = [];
        foreach ([
            'wedding' => ['customer' => 'couple', 'quote' => 'wedding', 'title' => '[ÖRNEK] Bodrum düğün prodüksiyonu', 'status' => 'planned', 'starts_at' => now()->addMonths(2), 'due_at' => now()->addMonths(2)->addDays(14), 'brief' => 'Planlama aşamasında. Mekân keşfi, çekim akışı ve ekip takvimi netleştirilecek.'],
            'hotel' => ['customer' => 'hotel', 'quote' => 'hotel', 'title' => '[ÖRNEK] Maison Lune fotoğraf ve film çekimi', 'status' => 'in_progress', 'starts_at' => now()->subDays(3), 'due_at' => now()->addDays(12), 'brief' => 'Teklif kabul edildi. Otel odaları, ortak alanlar ve gün batımı marka filmi çekiliyor.', 'delivered_at' => now()->subDays(2), 'delivery_note' => '[ÖRNEK] İlk fotoğraf seçkisi teslim edildi: https://example.test/galeri/maison-lune-sezon-1'],
            'resort' => ['customer' => 'agency', 'quote' => 'resort', 'title' => '[ÖRNEK] Al Noor Resort kampanya kapsamı', 'status' => 'on_hold', 'starts_at' => now()->addWeeks(3), 'due_at' => now()->addWeeks(5), 'brief' => 'Tarih, lokasyon erişimi ve teklif onayı netleşene kadar beklemede.'],
            'engagement' => ['customer' => 'couple', 'quote' => null, 'title' => '[ÖRNEK] Ada & Deniz nişan çekimi', 'status' => 'completed', 'starts_at' => now()->subMonths(2), 'due_at' => now()->subMonths(2)->addDays(3), 'brief' => 'Düğün paketinden önce küçük bir nişan çekimi; teslim edildi ve onaylandı.', 'delivered_at' => now()->subMonth(), 'delivery_note' => '[ÖRNEK] Nişan galerisi teslim edildi: https://example.test/galeri/ada-deniz-nisan'],
        ] as $key => $attributes) {
            $work = Work::withTrashed()->firstOrNew(['demo_key' => 'demo-work-'.$key]);
            if ($work->trashed()) {
                $work->restore();
            }
            $work->fill([
                ...collect($attributes)->except(['customer', 'quote'])->all(),
                'customer_id' => $customers[$attributes['customer']]->id,
                'quote_id' => $attributes['quote'] ? $quotes[$attributes['quote']]->id : null,
                'internal_notes' => 'Bu kayıt panel akışını denemek için oluşturulmuş örnektir.',
                'demo_key' => 'demo-work-'.$key,
                'published' => true,
            ]);
            $work->save();
            $works[$key] = $work;
        }

        \App\Models\RevisionRequest::firstOrCreate(
            ['work_id' => $works['hotel']->id, 'customer_note' => '[ÖRNEK] Gün batımı fotoğraflarında renk tonu çok sıcak kaldı, biraz daha soğuk ton rica ederiz.'],
            ['status' => 'requested', 'requested_at' => now()->subDay()]
        );

        \App\Models\RevisionRequest::firstOrCreate(
            ['work_id' => $works['engagement']->id, 'customer_note' => '[ÖRNEK] Deniz kenarındaki kareler için birkaç siyah-beyaz versiyon da rica ederiz.'],
            ['status' => 'approved', 'admin_response' => '[ÖRNEK] Siyah-beyaz versiyonlar galeriye eklendi.', 'requested_at' => now()->subWeeks(3), 'resolved_at' => now()->subWeeks(2)]
        );
    }
}
