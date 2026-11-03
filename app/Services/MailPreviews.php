<?php

namespace App\Services;

use App\Mail\BrandedMail;
use App\Mail\CustomerInvitationMail;
use App\Mail\CustomerPasswordMail;
use App\Mail\InquiryReceiptMail;
use App\Mail\InquiryReceivedMail;
use App\Mail\QuoteAcceptedMail;
use App\Mail\QuoteDeclinedMail;
use App\Mail\QuoteSentMail;
use App\Mail\RevisionRequestedMail;
use App\Mail\RevisionResolvedMail;
use App\Mail\WorkApprovedMail;
use App\Mail\WorkDeliveredMail;
use App\Models\Customer;
use App\Models\Inquiry;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\RevisionRequest;
use App\Models\Work;

class MailPreviews
{
    public const TYPES = [
        'quote-ready' => 'Müşteri · Teklif hazır',
        'quote-accepted' => 'Yönetici · Teklif kabul edildi',
        'quote-declined' => 'Yönetici · Teklif reddedildi',
        'delivery' => 'Müşteri · Teslimat incelemeye hazır',
        'delivery-approved' => 'Yönetici · Teslimat onaylandı',
        'revision-requested' => 'Yönetici · Revizyon talebi',
        'revision-accepted' => 'Müşteri · Revizyon kabul edildi',
        'revision-rejected' => 'Müşteri · Revizyon reddedildi',
        'invitation' => 'Müşteri · Panel daveti',
        'password' => 'Müşteri · Parola yenileme',
        'contact-receipt' => 'Müşteri · Mesaj alındı',
        'quote-receipt' => 'Müşteri · Proje talebi alındı',
        'contact-admin' => 'Yönetici · İletişim bildirimi',
        'quote-admin' => 'Yönetici · Teklif talebi bildirimi',
    ];

    public function make(string $type, string $language = 'tr'): BrandedMail
    {
        abort_unless(isset(self::TYPES[$type]) && in_array($language, ['tr', 'en']), 404);
        $customer = new Customer(['title' => 'Örnek Otel / Sample Hotel', 'email' => 'customer@example.test', 'preferred_locale' => $language]);
        $quote = new Quote(['title' => 'Hotel photography & film', 'quote_number' => 'CC-2026-001', 'amount' => 5000, 'currency' => 'EUR', 'valid_until' => today()->addDays(14), 'accepted_at' => now()]);
        $quote->id = 999999;
        $quote->setRelation('customer', $customer);
        $quote->setRelation('items', collect([new QuoteItem(['title' => $language === 'tr' ? 'Fotoğraf prodüksiyonu' : 'Photography production', 'description' => $language === 'tr' ? '2 gün · 30 rötuşlu fotoğraf' : '2 days · 30 retouched images', 'quantity' => 2, 'unit_price' => 1400]), new QuoteItem(['title' => $language === 'tr' ? 'Tanıtım filmi' : 'Brand film', 'quantity' => 1, 'unit_price' => 2200])]));
        $work = new Work(['title' => $quote->title, 'delivery_note' => $language === 'tr' ? 'Fotoğraf ve filminiz incelemeye hazır. Drive klasörlerini teslimat sayfanızdan açabilirsiniz.' : 'Your photos and film are ready to review. Open the Drive folders on your delivery page.']);
        $work->id = 999999;
        $work->setRelation('customer', $customer);
        $revision = new RevisionRequest(['status' => $type === 'revision-rejected' ? 'rejected' : 'approved', 'customer_note' => $language === 'tr' ? 'Açılışı kısaltıp son sahnede logoyu daha uzun tutalım.' : 'Please shorten the opening and hold the logo longer in the final shot.', 'admin_response' => $language === 'tr' ? 'Talebinizi değerlendirdik. Detayları görüşmek için bu e-postayı yanıtlayabilirsiniz.' : 'We have reviewed your request. Reply to this email to discuss the details.']);
        $revision->setRelation('work', $work);
        $inquiry = new Inquiry(['name' => $customer->title, 'email' => $customer->email, 'locale' => $language, 'type' => str_starts_with($type, 'quote-') ? 'quote' : 'contact', 'property' => 'Sample Hotel', 'country' => 'Türkiye', 'city' => 'Antalya', 'service' => 'Photography & film', 'budget' => '5.000 EUR', 'usage' => 'Website / Social media', 'deliverables' => '30 photos + 60 second film', 'timing' => 'December 2026', 'details' => $language === 'tr' ? 'Yeni sezon için otel fotoğrafları ve kısa tanıtım filmi hakkında görüşmek istiyoruz.' : 'We would like to discuss hotel photography and a short brand film for the new season.']);
        $inquiry->id = 999999;
        $quoteUrl = 'https://example.test/quote/preview';
        $workUrl = 'https://example.test/delivery/preview';
        $passwordUrl = 'https://example.test/panel/reset-password/preview';

        return match ($type) {
            'quote-ready' => new QuoteSentMail($quote, $quoteUrl),
            'quote-accepted' => new QuoteAcceptedMail($quote),
            'quote-declined' => new QuoteDeclinedMail($quote),
            'delivery' => new WorkDeliveredMail($work, $workUrl),
            'delivery-approved' => new WorkApprovedMail($work),
            'revision-requested' => new RevisionRequestedMail($revision),
            'revision-accepted', 'revision-rejected' => new RevisionResolvedMail($revision, $workUrl),
            'invitation' => new CustomerInvitationMail($customer, $passwordUrl),
            'password' => new CustomerPasswordMail($passwordUrl, $language),
            'contact-receipt', 'quote-receipt' => new InquiryReceiptMail($inquiry),
            'contact-admin', 'quote-admin' => new InquiryReceivedMail($inquiry),
        };
    }
}
