@extends('admin.layout')
@section('title','Müşteriler')
@section('content')
<div class="admin-heading"><div><span class="eyebrow">Müşteri ilişkileri</span><h1>Müşteriler</h1><p>İletişim bilgileri, teklifler ve yürütülen işleri birlikte takip edin.</p></div><a class="button" href="{{ route('admin.customers.create') }}">+ Müşteri ekle</a></div>
<form class="toolbar" method="GET"><div class="search"><input name="q" value="{{ request('q') }}" placeholder="Ad, firma veya e-posta ara"><button class="button secondary">Ara</button></div><span class="muted">{{ $customers->total() }} kayıt</span></form>
<div class="table-wrap"><table><thead><tr><th>Müşteri</th><th>Firma</th><th>E-posta</th><th>Telefon</th><th>Teklif</th><th>İş</th><th></th></tr></thead><tbody>@forelse($customers as $customer)<tr><td><strong>{{ $customer->title }}</strong>@if($customer->demo_key)<br><small>[ÖRNEK]</small>@endif</td><td>{{ $customer->company_name ?: '—' }}</td><td>{{ $customer->email ?: '—' }}</td><td>{{ $customer->phone ?: '—' }}</td><td>{{ $customer->quotes_count }}</td><td>{{ $customer->works_count }}</td><td><a class="button secondary" href="{{ route('admin.customers.edit',$customer) }}">Aç</a></td></tr>@empty<tr><td colspan="7" class="muted">Henüz müşteri yok.</td></tr>@endforelse</tbody></table></div><div class="pagination">{{ $customers->links() }}</div>
@endsection
