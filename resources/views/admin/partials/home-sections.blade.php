@if($sections)
<section class="home-section-manager" data-home-sections>
<div class="section-manager-heading"><div><h2 class="panel-title">Ana sayfa bölümleri</h2><p class="help">Her varyant kendi ayarlarını saklar. Bölümleri açıp kapatın, sıralayın ve gösterilecek kayıtları seçin. Taslak veya yayından kaldırılan kayıtlar ziyaretçiye gösterilmez.</p></div><span class="status">{{ count($sections) }} bölüm</span></div>
@foreach($sections as $key => $section)
@php($base = 'home_sections.'.$key)
<details class="home-section-card" data-home-section-card>
<summary><span class="home-section-name">{{ $section['label'] }}</span><span class="muted">Bölümü düzenle</span></summary>
<div class="home-section-body">
<div class="home-section-controls">
<input type="hidden" name="home_sections[{{ $key }}][visible]" value="0">
<label class="section-toggle"><input type="checkbox" data-section-visible name="home_sections[{{ $key }}][visible]" value="1" @checked(old($base.'.visible', $section['visible']))> Bu bölümü göster</label>
<input type="hidden" name="home_sections[{{ $key }}][position]" value="{{ old($base.'.position', $section['position']) }}" data-section-position>
<div class="section-move"><button type="button" class="button secondary" data-section-move="-1">↑ Yukarı</button><button type="button" class="button secondary" data-section-move="1">↓ Aşağı</button></div>
</div>
@if(isset($choices[$key]))
<div class="form-grid">
<div class="field"><label for="section-mode-{{ $key }}">İçerik kaynağı</label><select id="section-mode-{{ $key }}" name="home_sections[{{ $key }}][mode]" data-section-mode><option value="automatic" @selected(old($base.'.mode', $section['mode']) === 'automatic')>Otomatik · Yayındaki kayıtlar</option><option value="manual" @selected(old($base.'.mode', $section['mode']) === 'manual')>Benim seçtiğim kayıtlar</option></select></div>
<div class="field"><label for="section-limit-{{ $key }}">En fazla kaç kayıt gösterilsin?</label><input id="section-limit-{{ $key }}" type="number" min="1" max="24" name="home_sections[{{ $key }}][limit]" value="{{ old($base.'.limit', $section['limit']) }}"></div>
</div>
<div class="home-content-selection" data-section-selection>
<label class="field"><span>Kayıt ara</span><input type="search" placeholder="İsimle filtrele…" data-section-search></label>
<p class="help">Seçtiğiniz kayıtların sırasını oklarla değiştirebilirsiniz. Hiç seçim yapmazsanız bu bölümün seçkisi boş kalır.</p>
<div class="home-record-list" data-section-records>
@php($selectedIds = array_map('intval', old($base.'.items', $section['items'])))
@foreach($choices[$key]->sortBy(fn($record) => in_array($record->id, $selectedIds) ? array_search($record->id, $selectedIds) : count($selectedIds) + $record->id) as $record)
<div class="home-record-row" data-section-record>
@php($thumbnail = $key === 'references' ? $record->imageAsArray('logo', 'default', ['w' => 100]) : $record->coverImage(100))
<label>@if($thumbnail)<img src="{{ $thumbnail['src'] }}" alt="" loading="lazy">@endif<input type="checkbox" name="home_sections[{{ $key }}][items][]" value="{{ $record->id }}" @checked(in_array($record->id, $selectedIds))><span>{{ $record->translate('tr')?->title ?: $record->title }}<small>{{ $record->published ? 'Yayında' : 'Taslak · Sitede görünmez' }}</small></span></label>
<div><button type="button" data-record-move="-1" aria-label="{{ $record->title }} önceye taşı">↑</button><button type="button" data-record-move="1" aria-label="{{ $record->title }} sonraya taşı">↓</button></div>
</div>
@endforeach
</div>
</div>
@if($key === 'showreel')
<div class="field"><label>Öne çıkan film</label><select name="home_sections[{{ $key }}][featured_id]"><option value="">Varsayılan showreel</option>@foreach($choices[$key] as $film)<option value="{{ $film->id }}" @selected(old($base.'.featured_id', $section['featured_id']) == $film->id)>{{ $film->title }}{{ $film->published ? '' : ' · Taslak' }}</option>@endforeach</select></div>
@endif
@endif
@if(!empty($section['texts']))
<div class="form-grid">
@foreach(['tr' => 'Türkçe', 'en' => 'English'] as $locale => $language)
<details class="section-language"><summary>{{ $language }} bölüm metinleri</summary>
@foreach($section['texts'][$locale] ?? [] as $index => $text)
<div class="field"><label for="section-{{ $key }}-{{ $locale }}-{{ $index }}">{{ match($text['tag']) { 'h1' => 'Ana başlık', 'h2' => 'Bölüm başlığı', 'h3', 'h4' => 'Alt başlık', 'span' => 'Üst etiket', 'li' => 'Liste maddesi', default => 'Açıklama' } }}</label><textarea id="section-{{ $key }}-{{ $locale }}-{{ $index }}" name="home_sections[{{ $key }}][texts][{{ $locale }}][{{ $index }}]" rows="2">{{ old($base.'.texts.'.$locale.'.'.$index, $text['value']) }}</textarea></div>
@endforeach
</details>
@endforeach
</div>
@endif
@if($key === 'hero')<p class="help">Bu varyantın kapak, fotoğraf şeridi veya video alanını yukarıdaki görsel bölümünden düzenleyebilirsiniz.</p>@endif
</div>
</details>
@endforeach
</section>
@endif
