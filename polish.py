"""Reference identity layout, home logo motion and bilingual editorial ticker."""
from pathlib import Path
import re
D=Path('dist')
for lang in ('en','tr'):
 home=D/lang/'index.html';s=home.read_text();match=re.search(r'<div class="reference-grid">(.*?)</div></section>',s,re.S)
 if match:
  logos=match.group(1)
  # The match includes the final logo's closing tag, with the grid close outside.
  group='<div class="logo-group">'+logos+'</div>'
  clone=re.sub(r'\salt="[^"]*"',' alt=""',group).replace('class="logo-group"','class="logo-group" aria-hidden="true"')
  replacement='<div class="logo-marquee" tabindex="0" aria-label="'+('Referans markalar' if lang=='tr' else 'Selected client references')+'"><div class="logo-track">'+group+clone+'</div></div></section>'
  s=s[:match.start()]+replacement+s[match.end():]
 text=['Işık, mekân, insan','Her kare bir hikâye','Fotoğraftan filme'] if lang=='tr' else ['Light, space, people','Every frame, a story','From photography to film']
 group='<div class="ticker-group">'+''.join('<span>'+word+'</span><span class="ticker-star" aria-hidden="true">✳</span>' for word in text)+'</div>'
 clone=group.replace('class="ticker-group"','class="ticker-group" aria-hidden="true"')
 ticker='<div class="editorial-ticker" tabindex="0" aria-label="'+' · '.join(text)+'"><div class="ticker-track">'+group+clone+'</div></div>'
 s=s.replace('<section class="section discipline-section">',ticker+'<section class="section discipline-section">',1)
 home.write_text(s)

# A local SVG filter turns white pixels transparent without changing logo dimensions.
# Marks use the site's ink colour; the original files and their proportions are retained.
logo_filter = '<svg class="logo-filter-defs" xmlns="http://www.w3.org/2000/svg" width="0" height="0" aria-hidden="true" focusable="false"><defs><filter id="logo-ink" color-interpolation-filters="sRGB"><feColorMatrix type="matrix" values="0 0 0 0 0.12549 0 0 0 0 0.14118 0 0 0 0 0.12549 -0.6378 -2.1456 -0.2166 0 3"/><feComposite in2="SourceGraphic" operator="in"/></filter></defs></svg>'
for page in D.rglob('index.html'):
    html = page.read_text()
    if 'class="reference-logo"' in html:
        html = html.replace('<body>', '<body>' + logo_filter, 1)
        page.write_text(html)
