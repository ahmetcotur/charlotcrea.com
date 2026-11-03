@php
$money = fn($amount) => app()->getLocale()==='tr' ? number_format((float)$amount,2,',','.') : number_format((float)$amount,2,'.',',');
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow">
<title>{{ $quote->quote_number }} · {{ $quote->title }} · Charlot Crea</title>
<style>
@page { margin: 30px 36px; }
*{box-sizing:border-box}body{margin:0;background:#eeede9;color:#222722;font-family:DejaVu Sans,Arial,sans-serif;font-size:12px;line-height:1.65}.shell{max-width:1000px;margin:40px auto}.document{background:#fff;padding:46px 50px;border-top:7px solid #344637}.brand-table,.meta-table,.items{width:100%;border-collapse:collapse}.brand-table td{vertical-align:middle}.logo{width:115px;height:auto}.brand-note{text-align:right;color:#666b61;font-size:10px;letter-spacing:2px}.eyebrow{text-transform:uppercase;letter-spacing:3px;font-size:10px;color:#6b775d;margin:40px 0 10px}h1{font-family:DejaVu Serif,Georgia,serif;font-weight:normal;font-size:32px;line-height:1.25;margin:0 0 24px}.meta-table{border-top:1px solid #dddcd2;border-bottom:1px solid #dddcd2;margin:24px 0}.meta-table td{width:50%;padding:18px 0;vertical-align:top}.label{font-size:9px;text-transform:uppercase;letter-spacing:1.5px;color:#737a6e;display:block}.meta-value{font-size:13px;margin-top:5px}.status{display:inline-block;background:#edf0e8;padding:4px 10px;color:#344637;font-size:10px}h2{font-size:15px;font-weight:normal;margin:30px 0 12px}.scope{white-space:pre-wrap;color:#50564e;overflow-wrap:anywhere}.items{margin-top:14px;table-layout:fixed}.items th{background:#344637;color:white;font-size:9px;text-transform:uppercase;letter-spacing:1px;padding:12px 10px;text-align:right}.items th:first-child{text-align:left;width:47%}.items td{padding:16px 10px;border-bottom:1px solid #e5e6de;vertical-align:top;text-align:right;overflow-wrap:anywhere}.items td:first-child{text-align:left}.items tr{page-break-inside:avoid}.items thead{display:table-header-group}.description{display:block;font-size:10px;color:#6f746c;white-space:pre-wrap;margin-top:4px}.total{text-align:right;padding:22px 15px;background:#f0f2eb;margin-top:16px;page-break-inside:avoid}.total strong{font-size:25px;font-weight:normal;margin-left:16px}.closing{border-top:1px solid #dddcd2;margin-top:38px;padding-top:18px;font-size:10px;color:#747a70}.closing a{color:inherit}.toolbar{display:flex;justify-content:space-between;align-items:center;margin:0 0 20px;gap:15px}.toolbar a{color:#344637;text-decoration:none}.button{border:0;border-radius:3px;background:#344637;color:white!important;padding:13px 22px;text-decoration:none;font:inherit;cursor:pointer;display:inline-block}.secondary{background:transparent;color:#344637!important;border:1px solid #acb3a4}.actions{padding:26px 0;display:flex;gap:12px;flex-wrap:wrap}.actions form{margin:0}.note{color:#626a5c;font-size:12px}body.pdf{background:white}.pdf .shell{margin:0;max-width:none}.pdf .document{padding:20px 0}.pdf h1{font-size:28px}
@media(max-width:650px){.shell{margin:16px}.document{padding:24px 18px}h1{font-size:26px}.items th,.items td{padding:10px 5px;font-size:10px}.brand-note{letter-spacing:0;font-size:9px}.toolbar{font-size:11px}.total strong{font-size:22px}}
@media print{body{background:white}.shell{margin:0}.document{padding:0;border-top:5px solid #344637}.toolbar,.actions,.note{display:none}}
</style>
</head>
<body @if($pdf) class="pdf" @endif>
<div class="shell">
@if(!$pdf)<nav class="toolbar" aria-label="{{ __('Quote actions') }}"><a href="/">Charlot Crea / {{ __('Quote') }}</a><a class="button secondary" href="{{ $pdfUrl }}">{{ __("Download PDF") }} ↓</a></nav>@endif
<main class="document">
<table class="brand-table" role="presentation"><tr><td><img class="logo" src="{{ $logo }}" alt="Charlot Crea"></td><td class="brand-note">PHOTOGRAPHY & FILM<br>CHARLOT CREA</td></tr></table>
<p class="eyebrow">{{ __("Your quote") }} · {{ $quote->quote_number }}</p>
<h1>{{ $quote->title }}</h1>
<span class="status">{{ ['draft'=>__('Draft'),'sent'=>__('Awaiting approval'),'accepted'=>__('Accepted'),'declined'=>__('Declined'),'expired'=>__('Expired')][$quote->status] ?? $quote->status }}</span>
<table class="meta-table" role="presentation"><tr><td><span class="label">{{ __("Prepared for") }}</span><div class="meta-value"><strong>{{ $quote->customer?->company_name ?: $quote->customer?->title }}</strong>@if($quote->customer?->company_name)<br>{{ $quote->customer?->title }}@endif</div></td><td><span class="label">{{ __("Quote details") }}</span><div class="meta-value">{{ $quote->quote_number }}<br>{{ __("Date") }}: {{ ($quote->sent_at ?? $quote->created_at)?->format('d.m.Y') }}@if($quote->valid_until)<br>{{ __("Validity") }}: {{ $quote->valid_until->format('d.m.Y') }}@endif</div></td></tr></table>
@if($quote->scope)<h2>{{ __("Project scope") }}</h2><div class="scope">{{ $quote->scope }}</div>@endif
@if($quote->items->isNotEmpty())<h2>{{ __("Services and investment") }}</h2><table class="items"><thead><tr><th>{{ __("Service / description") }}</th><th>{{ __("Quantity") }}</th><th>{{ __("Unit price") }}</th><th>{{ __("Amount") }}</th></tr></thead><tbody>
@foreach($quote->items as $item)<tr><td><strong>{{ $item->title }}</strong>@if($item->description)<span class="description">{{ $item->description }}</span>@endif</td><td>{{ rtrim(rtrim(number_format((float)$item->quantity,2,'.',''), '0'),'.') }}</td><td>{{ $money($item->unit_price) }} {{ $quote->currency }}</td><td>{{ $money($item->line_total) }} {{ $quote->currency }}</td></tr>@endforeach
</tbody></table>@endif
<div class="total"><span>{{ __("Total") }}</span><strong>{{ $money($quote->amount) }} {{ $quote->currency }}</strong></div>
<div class="closing"><strong>{{ __('Let’s tell a remarkable story together.') }}</strong><br>{{ config('site.email') }} · {{ config('site.telephone') }}<br>{{ config('site.address') }}</div>
</main>
@if(!$pdf)
@if($actionable)<div class="actions"><form method="POST" action="{{ $acceptUrl }}">@csrf<button class="button" type="submit">{{ __("Accept quote") }}</button></form><form method="POST" action="{{ $declineUrl }}">@csrf<button class="button secondary" type="submit">{{ __("Decline quote") }}</button></form></div><p class="note">{{ __("Your approval creates your project automatically.") }}</p>
@elseif($quote->status==='expired')<p class="note">{{ __("This quote has expired. Please contact us for an updated quote.") }}</p>
@else<p class="note">{{ __("Your response has been recorded. No further action is needed.") }}</p>@endif
@endif
</div></body></html>
