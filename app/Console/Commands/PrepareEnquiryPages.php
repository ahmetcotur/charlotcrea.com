<?php

namespace App\Console\Commands;

use App\Models\Inquiry;
use App\Models\Page;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PrepareEnquiryPages extends Command
{
    protected $signature = 'charlot:prepare-enquiry-pages';

    protected $description = 'Initialize separate bilingual contact and quote request pages without replacing edits';

    public function handle(): int
    {
        DB::transaction(function () {
            $quote = Page::firstOrCreate(['import_key' => '/en/request-a-quote/'], ['published' => true, 'position' => (int) Page::max('position') + 1]);
            $copy = [
                'tr' => ['title' => 'Teklif al', 'path' => '/tr/teklif-al/', 'heading' => 'Projenize özel\nbir teklif hazırlayalım.', 'intro' => 'Çekim yerini, ihtiyacınızı ve beklentilerinizi paylaşın. Projenizin kapsamını birlikte netleştirelim.', 'seo_title' => 'Fotoğraf ve Film Çekimi Teklifi Alın | Charlot Crea', 'description' => 'Otel, mimari ve marka fotoğrafçılığı veya tanıtım filmi projeniz için konum, kapsam ve takvim bilgilerini paylaşarak Charlot Crea’dan teklif isteyin.'],
                'en' => ['title' => 'Request a quote', 'path' => '/en/request-a-quote/', 'heading' => 'Let’s shape a quote\naround your project.', 'intro' => 'Share your location, creative needs and expectations. We will help define the scope of your production.', 'seo_title' => 'Request a Photography & Film Quote | Charlot Crea', 'description' => 'Request a tailored quote for hotel, architecture or brand photography and film production. Share your location, scope and schedule with Charlot Crea.'],
            ];
            foreach ($copy as $locale => $fields) {
                $fields['heading'] = str_replace('\\n', "\n", $fields['heading']);
                $quote->translations()->firstOrCreate(['locale' => $locale], ['active' => true, ...$fields]);
            }
            $contact = Page::with('translations')->where('import_key', '/en/contact/')->first();
            if ($contact && ! ($contact->source_data['legacy_inquiry_types_initialized'] ?? false)) {
                Inquiry::where('type', 'contact')->whereNull('project_type')
                    ->whereNotNull('property')->where('property', '!=', '')
                    ->whereNotNull('country')->where('country', '!=', '')
                    ->whereNotNull('city')->where('city', '!=', '')
                    ->whereNotNull('service')->where('service', '!=', '')
                    ->update(['type' => 'quote']);
                $contact->source_data = array_replace($contact->source_data ?? [], ['legacy_inquiry_types_initialized' => true]);
                $contact->save();
            }
            if ($contact && ! ($contact->source_data['contact_form_initialized'] ?? false)) {
                $copy = ['tr' => ['heading' => "Bir sorunuz mu var?\nKonuşalım.", 'intro' => 'Genel sorularınız, iş birlikleri ve iletişime geçmek istediğiniz diğer konular için bize yazın.', 'seo_title' => 'İletişim | Charlot Crea', 'description' => 'Charlot Crea ile iletişime geçin. Genel sorularınız ve iş birliği talepleriniz için iletişim formunu kullanın veya Antalya merkezli ekibimize ulaşın.'], 'en' => ['heading' => "Have a question?\nLet’s talk.", 'intro' => 'Get in touch for general questions, collaborations or anything else you would like to discuss.', 'seo_title' => 'Contact Us | Charlot Crea', 'description' => 'Contact Charlot Crea for general questions and collaborations. Send a message through our contact form or get in touch with our Antalya-based team.']];
                foreach ($copy as $locale => $fields) {
                    $contact->translate($locale)?->fill($fields)->save();
                }
                $contact->source_data = array_replace($contact->source_data ?? [], ['contact_form_initialized' => true]);
                $contact->save();
            }
        });
        $this->info('Contact and project quote forms initialized in Turkish and English.');

        return self::SUCCESS;
    }
}
