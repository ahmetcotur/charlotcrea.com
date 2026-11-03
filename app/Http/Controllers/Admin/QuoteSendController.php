<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\QuoteSentMail;
use App\Models\Quote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class QuoteSendController extends Controller
{
    public function __invoke(Quote $quote): RedirectResponse
    {
        if (in_array($quote->status, ['accepted', 'declined'])) {
            return back()->with('error', 'Yanıtlanmış teklif yeniden gönderilemez. Yeni bir teklif oluşturun.');
        }

        if ($quote->isExpired()) {
            return back()->with('error', 'Teklifin geçerlilik tarihi geçmiş, önce tarihi güncelleyin.');
        }

        $quote->update(['status' => 'sent']);

        if (blank($quote->customer->email)) {
            return back()->with('success', 'Teklif "gönderildi" olarak işaretlendi. Müşterinin e-postası tanımlı değil — bağlantıyı aşağıdan kopyalayıp WhatsApp ile iletebilirsiniz.');
        }

        Mail::to($quote->customer->email)->queue(new QuoteSentMail($quote, $quote->signedShowUrl()));

        return back()->with('success', 'Teklif müşteriye e-posta ile gönderildi.');
    }
}
