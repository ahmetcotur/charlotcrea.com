<?php

namespace App\Mail;

use App\Services\SiteSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

abstract class BrandedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $language = 'tr';

    public int $tries = 3;

    public array $backoff = [30, 120, 600];

    protected function useLanguage(?string $language): void
    {
        $this->language = in_array($language, ['tr', 'en'], true) ? $language : 'tr';
        $this->locale($this->language);
        $this->afterCommit();
    }

    protected function mailText(string $key, array $replace = []): string
    {
        return trans('mail.'.$key, $replace, $this->language);
    }

    protected function money(float $amount): string
    {
        return $this->language === 'tr' ? number_format($amount, 2, ',', '.') : number_format($amount, 2, '.', ',');
    }

    abstract protected function messageData(): array;

    public function build()
    {
        app(SiteSettings::class)->apply();
        $data = array_merge(['name' => null, 'fields' => [], 'notes' => [], 'items' => [], 'actionUrl' => null, 'actionLabel' => null, 'replyAddress' => config('site.email')], $this->messageData());
        if (filter_var($data['replyAddress'], FILTER_VALIDATE_EMAIL)) {
            $this->replyTo($data['replyAddress']);
        }

        return $this->subject(preg_replace('/[\r\n]+/u', ' ', $data['subject']))->view('mail.branded', $data)->text('mail.branded-text', $data);
    }
}
