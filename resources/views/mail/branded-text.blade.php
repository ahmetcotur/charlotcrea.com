{{ config('site.name') }} · {{ $heading }}

@if($name){{ __('mail.hello',['name'=>$name],$language) }}
@endif
{{ $intro }}

@foreach($fields as $label=>$value)@if(filled($value)){{ $label }}: {{ $value }}
@endif @endforeach
@foreach($items as $item){{ $item['title'] }} · {{ $item['quantity'] }} · {{ $item['amount'] }}
@if($item['description']){{ $item['description'] }}
@endif @endforeach
@foreach($notes as $note){{ $note['label'] ?? '' }}
{{ $note['text'] }}

@endforeach
@if($actionUrl){{ $actionLabel }}: {{ $actionUrl }}
@endif
{{ __('mail.help',[],$language) }}
{{ config('site.email') }} · {{ config('site.telephone') }}
{{ config('site.address') }}
