@if($customer->exists)
<section class="panel"><h2 class="panel-title">Müşteri paneli erişimi</h2><p class="muted">Müşteri kendi tekliflerini, işlerini ve teslimat dosyalarını giriş yaparak görebilir. Giriş: <a href="{{ route('customer.login') }}" target="_blank" rel="noopener">{{ route('customer.login') }}</a></p>
@if($customer->account)<p><span class="status">{{ $customer->account->enabled ? ($customer->account->activated_at ? 'Aktif hesap' : 'Parola belirlemesi bekleniyor') : 'Erişim kapalı' }}</span> · {{ $customer->account->email }}</p>@endif
@if(session('customer_invite_url'))<div class="notice"><strong>Parola oluşturma bağlantısı · 60 dakika</strong><input style="width:100%;margin:10px 0;padding:10px" readonly value="{{ session('customer_invite_url') }}" aria-label="Müşteri davet bağlantısı" onclick="this.select()"><p>Bu bağlantıyı yalnızca ilgili müşteriyle paylaşın. Oluşturmak e-posta göndermez.</p></div>@endif
<div class="quicklinks"><form method="POST" action="{{ route('admin.customers.access.invite',$customer) }}">@csrf<button class="button" @disabled(!$customer->email)>Panel erişimi aç / Yeni davet bağlantısı</button></form>@if($customer->account?->enabled)<form method="POST" action="{{ route('admin.customers.access.revoke',$customer) }}">@csrf<button class="button danger">Erişimi kapat</button></form>@endif</div>
@if(!$customer->email)<p class="muted">Önce müşteri e-postasını kaydedin.</p>@endif
</section>
@endif
