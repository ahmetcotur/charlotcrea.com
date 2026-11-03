<section class="form-shell">
    <div>
        <p class="bodytext">Projenizi ve aklınızdaki çalışmayı kısaca anlatın.</p>
        <p class="form-note">01 · Brief’inizi paylaşın<br>02 · Kapsamı ve takvimi konuşalım<br>03 · Prodüksiyonu şekillendirelim</p>
        <p class="demo-notice">Gönderdiğiniz brief yönetim panelindeki İletişim talepleri bölümüne kaydedilir.</p>
    </div>
    <div class="formbox">
        @if(session('inquiry-success'))<p class="form-success" role="status">{{ session('inquiry-success') }}</p>@endif
        @if($errors->any())<p class="form-error" role="alert">Lütfen işaretli alanları kontrol edip tekrar gönderin.</p>@endif
        <div class="stepbar"><span class="done"></span><span></span><span></span></div>
        <form id="brief" method="POST" action="{{ route('contact-inquiries.store') }}" data-initial-step="{{ $errors->any() ? 2 : 0 }}" @if(session('inquiry-success')) hidden @endif>
            @csrf
            <input type="hidden" name="locale" value="tr">
            <label class="contact-honeypot" aria-hidden="true">Web sitesi<input name="website" tabindex="-1" autocomplete="off"></label>
            <div class="form-step">
                <span class="label">Adım 1 / Projeniz</span><h2>Hikâye nerede başlıyor?</h2>
                <label>Şirket veya proje adı<input name="property" required autocomplete="organization" value="{{ old('property') }}" placeholder="Şirketinizin veya projenizin adı"></label>
                <label>Ülke<select required name="country"><option value="">{{ __('Select country') }}</option>@foreach($site->catalog(\App\Models\Destination::class)->whereNull('parent_id') as $destination)<option value="{{ $destination->country ?: $destination->translate('en')->title }}" @selected(old('country') === ($destination->country ?: $destination->translate('en')->title))>{{ $destination->title }}</option>@endforeach<option value="Other" @selected(old('country') === 'Other')>Diğer</option></select></label>
                <label>Şehir veya destinasyon<input name="city" required value="{{ old('city') }}" placeholder="Örn. Dubai"></label>
            </div>
            <div class="form-step">
                <span class="label">Adım 2 / Vizyonunuz</span><h2>Ne üretmek istersiniz?</h2>
                <label>Hizmet<select name="service" required><option value="">Hizmet seçin</option><option value="Photography" @selected(old('service') === 'Photography')>Fotoğraf</option><option value="Film production" @selected(old('service') === 'Film production')>Film prodüksiyonu</option><option value="Photography & film" @selected(old('service') === 'Photography & film')>Fotoğraf &amp; film</option></select></label>
                <label>Tercih edilen tarih<input name="timing" value="{{ old('timing') }}" placeholder="Tarihler veya tahmini ay"></label>
                <label>Projenizi kısaca anlatın<textarea name="details" required placeholder="Mekânlar, teslimler, referanslar…">{{ old('details') }}</textarea></label>
            </div>
            <div class="form-step">
                <span class="label">Adım 3 / Bilgileriniz</span><h2>Tanışalım.</h2>
                <label>Adınız<input name="name" required autocomplete="name" value="{{ old('name') }}"></label>
                <label>İş e-postanız<input name="email" type="email" required autocomplete="email" value="{{ old('email') }}"></label>
                <div id="review" class="review"></div>
                <label class="consent"><input type="checkbox" name="consent" value="1" required @checked(old('consent'))> Bu formdaki bilgilerimin proje talebim için kullanılmasını kabul ediyorum.</label>
                <p class="demo-notice">Talebiniz ekibe iletilmek üzere güvenli biçimde kaydedilir.</p>
            </div>
            <div class="form-actions">
                <button type="button" id="back" class="btn light" hidden>Geri</button>
                <button type="submit" id="next" class="btn">Devam ↗</button>
            </div>
        </form>
        <div id="complete" @if(!session('inquiry-success')) hidden @endif><span class="label">Talebiniz alındı</span><h2 style="margin-top:20px">Brief’iniz ekibimize ulaştı.</h2><p class="bodytext">{{ session('inquiry-success') ?: 'En kısa sürede sizinle iletişime geçeceğiz.' }}</p></div>
    </div>
</section>
