"""Bilingual HTML, editorial data, language alternates and final sitemap build."""
from pathlib import Path
from html.parser import HTMLParser
from html import escape,unescape
from urllib.parse import urlsplit,urlunsplit,urlencode,quote
import json,re,copy,math,xml.etree.ElementTree as ET
D=Path('dist');base=seo['base'];config=seo['config'];production=seo['production'];dimensions=seo['dimensions']
translations=json.loads(Path('data/tr.json').read_text());posts=json.loads(Path('data/blog.json').read_text());projects=seo['projects'];metadata=dict(seo['metadata'])
route_segments={'photography':'fotograf','about':'hakkimizda','references':'referanslar','services':'hizmetler','contact':'iletisim','destinations':'destinasyonlar','turkey':'turkiye','uae':'bae','egypt':'misir','saudi-arabia':'suudi-arabistan','projects':'projeler'}
def trroute(path):return '/tr/'+'/'.join(route_segments.get(s,s) for s in path.strip('/').split('/')[1:])+('/' if path!='/en/' else '')
routes={path:trroute(path) for path in metadata}
routes['/en/blog/']='/tr/blog/'
for post in posts:routes['/en/blog/'+post['en']['slug']+'/']='/tr/blog/'+post['tr']['slug']+'/'
reverse={v:k for k,v in routes.items()}
def url_tr(url):
 parts=urlsplit(unescape(url));path=parts.path
 if (not parts.netloc or url.startswith(base+'/')) and path in routes:
  return urlunsplit((parts.scheme,parts.netloc,routes[path],parts.query,parts.fragment))
 return url
places={'UAE':'BAE','United Arab Emirates':'Birleşik Arap Emirlikleri','Egypt':'Mısır','Saudi Arabia':'Suudi Arabistan','Istanbul':'İstanbul','Cairo':'Kahire','Makkah':'Mekke'}
def place(text):
 for en,tr in sorted(places.items(),key=lambda x:-len(x[0])):text=re.sub(r'\b'+re.escape(en)+r'\b',lambda m:tr,text)
 return text
# Exact editorial translation plus patterns for fact-based project descriptions.
def tr(text):
 stripped=text.strip()
 if stripped in translations:return text.replace(stripped,translations[stripped])
 if not stripped:return text
 patterns=[
 (r'(\d+) photographs / Selected collection',lambda m:m[1]+' fotoğraf / Seçili koleksiyon'),
 (r'Next project / (.+) ↗',lambda m:'Sonraki proje / '+m[1]+' ↗'),
 (r'More work in (.+) ↗',lambda m:place(m[1])+': diğer çalışmalar ↗'),
 (r'Explore photography in (.+) ↗',lambda m:place(m[1])+': fotoğrafları keşfedin ↗'),
 (r'Photography / (.+)',lambda m:'Fotoğraf / '+place(m[1])),
 (r'View (.+) photograph (\d+)',lambda m:m[1]+' fotoğrafını görüntüle '+m[2]),
 (r'(.+) — photograph (\d+)',lambda m:m[1]+' — fotoğraf '+m[2]),
 (r'(.+) photography gallery',lambda m:m[1]+' fotoğraf galerisi'),
 (r'(.+) — selected photography from the Charlot Crea portfolio',lambda m:m[1]+' — Charlot Crea portfolyosundan seçili fotoğraf'),
 (r'(.+) portfolio photograph',lambda m:m[1]+' portfolyo fotoğrafı'),
 (r'A selected photography collection from (.+) in (.+), (.+)\. Explore (\d+) photographs from the original Charlot Crea portfolio\.',lambda m:m[1]+' için '+place(m[2])+', '+place(m[3])+' konumunda hazırlanan fotoğraf koleksiyonu. Charlot Crea portfolyosundan '+m[4]+' seçili fotoğrafı keşfedin.'),
 (r'Selected photography from (.+)\. Explore the galleries and discuss a new project in (.+)\.',lambda m:m[1]+' projelerinden seçili fotoğraflar. Galerileri keşfedin; '+place(m[2])+' için yeni projenizi konuşalım.')]
 for pattern,fn in patterns:
  m=re.fullmatch(pattern,stripped)
  if m:return text.replace(stripped,fn(m))
 if stripped=='Selected Dubai photography projects from the Charlot Crea portfolio, including 25 Hours Dubai, Raffles The Palm and Rixos The Palm.':return 'Charlot Crea portfolyosundan 25 Hours Dubai, Raffles The Palm ve Rixos The Palm dâhil seçili Dubai fotoğraf projeleri.'
 # Localize geographic labels while preserving proper hotel names.
 if '·' in stripped or ',' in stripped:
  if not any(word in stripped for word in ('Explore','Discover','project','photography')):return place(text)
 return text
class Translate(HTMLParser):
 def __init__(self):super().__init__(convert_charrefs=False);self.out=[];self.raw=0
 def handle_starttag(self,tag,attrs):
  a=dict(attrs)
  if tag in ('script','style'):self.raw+=1
  if tag=='html':a['lang']='tr'
  if tag=='option' and 'value' not in a:a['value']=None # Assigned by prepass to preserve form values.
  for k,v in list(a.items()):
   if v is None:continue
   if k in ('href','action'):a[k]=url_tr(v)
   if k in ('alt','aria-label','placeholder','title'):a[k]=tr(v)
  self.out.append('<'+tag+''.join(' '+k+('="'+escape(v,quote=True)+'"' if v is not None else '') for k,v in a.items())+'>')
 def handle_startendtag(self,tag,attrs):self.handle_starttag(tag,attrs)
 def handle_endtag(self,tag):
  if tag in ('script','style'):self.raw=max(0,self.raw-1)
  self.out.append('</'+tag+'>')
 def handle_data(self,data):self.out.append(data if self.raw else escape(tr(unescape(data)),quote=False))
 def handle_entityref(self,name):self.out.append('&'+name+';')
 def handle_charref(self,name):self.out.append('&#'+name+';')
 def handle_decl(self,d):self.out.append('<!'+d+'>')
 def handle_comment(self,d):self.out.append('<!--'+d+'-->')
def translated_html(html):
 html=re.sub(r'<option>([^<]+)</option>',lambda m:'<option value="'+escape(unescape(m[1]),quote=True)+'">'+m[1]+'</option>',html)
 parser=Translate();parser.feed(html);return ''.join(parser.out)
def graph_of(html):return json.loads(re.search(r'<script type="application/ld\+json">(.*?)</script>',html,re.S).group(1))['@graph']
def meta_head(html,lang,path,title,description,image,graph,article=False):
 locale='tr_TR' if lang=='tr' else 'en_US';other='en_US' if lang=='tr' else 'tr_TR'
 oldhead=re.search(r'<head>(.*?)</head>',html,re.S).group(1)
 styles=''.join(dict.fromkeys(re.findall(r'<link\b[^>]*(?:rel="stylesheet"|rel="icon")[^>]*>',oldhead)))
 if '/editorial.css' not in styles:styles+='<link rel="stylesheet" href="/editorial.css">'
 url=base+path;enpath=reverse[path] if lang=='tr' else path;trpath=routes[enpath]
 social_image=config['social_image'];social_alt='Charlot Crea logosu' if lang=='tr' else 'Charlot Crea logo'
 head='<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
 head+='<title>'+escape(title)+'</title><meta name="description" content="'+escape(description,quote=True)+'">'
 head+='<meta name="robots" content="'+('index,follow,max-image-preview:large' if production else 'noindex,nofollow')+'">'
 head+='<link rel="canonical" href="'+url+'">'
 for code,target in [('en',enpath),('tr',trpath),('x-default',enpath)]:head+=f'<link rel="alternate" hreflang="{code}" href="{base+target}">'
 props={'og:type':'article' if article else 'website','og:site_name':'Charlot Crea','og:locale':locale,'og:locale:alternate':other,'og:title':title,'og:description':description,'og:url':url,'og:image':base+social_image,'og:image:alt':social_alt,'og:image:type':'image/png'}
 dims=dimensions(D/social_image.lstrip('/'))
 if dims:props.update({'og:image:width':str(dims[0]),'og:image:height':str(dims[1])})
 if article:props.update({'article:published_time':'2026-10-31','article:author':base+('/tr/hakkimizda/' if lang=='tr' else '/en/about/')})
 head+=''.join(f'<meta property="{k}" content="{escape(v,quote=True)}">' for k,v in props.items())
 for k,v in {'twitter:card':'summary_large_image','twitter:title':title,'twitter:description':description,'twitter:image':base+social_image,'twitter:image:alt':social_alt,'theme-color':'#f6f5f0'}.items():head+=f'<meta name="{k}" content="{escape(v,quote=True)}">'
 head+=styles+'<script type="application/ld+json">'+json.dumps({'@context':'https://schema.org','@graph':graph},ensure_ascii=False).replace('<','\\u003c')+'</script>'
 return re.sub(r'<head>.*?</head>',lambda m:'<head>'+head+'</head>',html,flags=re.S)
# Blog content: five actual editorial examples in each language.
template=(D/'en/film/index.html').read_text()
def breadcrumbs(lang,items):
 home='/'+lang+'/';label='Ana sayfa' if lang=='tr' else 'Home'
 items=[(label,home)]+items
 return '<nav class="seo-breadcrumb" aria-label="'+('Sayfa yolu' if lang=='tr' else 'Breadcrumb')+'">'+('<span aria-hidden="true"> / </span>').join(f'<a href="{u}">{escape(n)}</a>' if i<len(items)-1 else f'<span aria-current="page">{escape(n)}</span>' for i,(n,u) in enumerate(items))+'</nav>'
def card(post,lang):
 d=post[lang];url='/'+lang+'/blog/'+d['slug']+'/'
 return f'<a class="journal-card" href="{url}"><div class="journal-card-image"><img src="{post["image"]}" alt="{escape(d["title"],quote=True)}" loading="lazy" decoding="async"></div><div class="journal-card-copy"><span class="label">{escape(d["category"])}</span><h2>{escape(d["title"])}</h2><p>{escape(d["excerpt"])}</p><span class="text-link">'+('Yazıyı okuyun ↗' if lang=='tr' else 'Read article ↗')+'</span></div></a>'
blog_records={}
for lang in ('en','tr'):
 index='/'+lang+'/blog/';blogname='Blog'
 body=breadcrumbs(lang,[(blogname,index)])+'<section class="page-intro journal-intro"><span class="label">'+('Çalışma notları' if lang=='tr' else 'Editorial notes')+'</span><h1>'+('Görüntünün arkasındaki<br>fikirler.' if lang=='tr' else 'Ideas behind<br>the image.')+'</h1><p>'+('Fotoğraf, film ve prodüksiyonun arkasındaki pratik kararlar üzerine notlar.' if lang=='tr' else 'Notes on photography, film and the practical decisions behind a production.')+'</p></section><section class="section journal-grid">'+''.join(card(post,lang) for post in posts)+'</section>'
 blog_records[index]={'title':('Fotoğraf ve Film Blogu | Charlot Crea' if lang=='tr' else 'Photography & Film Journal | Charlot Crea'),'description':'Fotoğraf, film ve prodüksiyon planlaması üzerine Charlot Crea’dan notlar ve pratik rehberler.' if lang=='tr' else 'Photography, film and production planning notes from Charlot Crea. Explore practical guides for your next creative project.','image':'/assets/hero.jpg','body':body,'kind':'Blog','lang':lang}
 for num,post in enumerate(posts):
  d=post[lang];url=index+d['slug']+'/';date='31 Ekim 2026' if lang=='tr' else '31 October 2026';words=len(' '.join(p for _,p in d['sections']).split());minutes=max(1,math.ceil(words/180))
  body=breadcrumbs(lang,[(blogname,index),(d['title'],url)])+'<article class="journal-article"><header class="article-opening"><span class="label">'+escape(d['category'])+'</span><h1>'+escape(d['title'])+'</h1><p class="article-lead">'+escape(d['excerpt'])+'</p><div class="article-meta"><span>Charlot Crea</span><time datetime="2026-10-31">'+date+'</time><span>'+str(minutes)+(' dk okuma' if lang=='tr' else ' min read')+'</span><span>'+('Örnek yazı' if lang=='tr' else 'Sample article')+'</span></div></header><figure class="article-image"><img src="'+post['image']+'" alt="'+escape(d['title'],quote=True)+'" fetchpriority="high"><figcaption>'+('Charlot Crea portfolyosundan.' if lang=='tr' else 'From the Charlot Crea portfolio.')+'</figcaption></figure><div class="article-layout"><aside class="article-toc" aria-label="'+('İçindekiler' if lang=='tr' else 'Contents')+'"><span class="label">'+('Bu yazıda' if lang=='tr' else 'In this article')+'</span>'+''.join(f'<a href="#section-{i+1}">{escape(h)}</a>' for i,(h,p) in enumerate(d['sections']))+'</aside><div class="article-body">'+''.join(f'<section id="section-{i+1}"><h2>{escape(h)}</h2><p>{escape(p)}</p></section>' for i,(h,p) in enumerate(d['sections']))+'<div class="article-end"><h2>'+('Sıradaki projenizi konuşalım.' if lang=='tr' else 'Let’s discuss your next project.')+'</h2><a class="btn" href="'+('/tr/iletisim/' if lang=='tr' else '/en/contact/')+'">'+('İletişime geçin ↗' if lang=='tr' else 'Get in touch ↗')+'</a></div></div></div></article><section class="section related-articles"><div class="section-head"><h2>'+('İlgili yazılar' if lang=='tr' else 'Related articles')+'</h2><a class="text-link" href="'+index+'">'+('Bloga dön ↗' if lang=='tr' else 'Back to the journal ↗')+'</a></div><div class="journal-grid">'+''.join(card(posts[(num+i)%len(posts)],lang) for i in (1,2))+'</div></section>'
  blog_records[url]={'title':d['title']+' | Charlot Crea','description':d['excerpt'],'image':post['image'],'body':body,'kind':'BlogPosting','lang':lang,'post':post}
for path,record in blog_records.items():
 s=re.sub(r'<main>.*?</main>',lambda m:'<main>'+record['body']+'</main>',template,flags=re.S)
 s=re.sub(r'<div class="brand-reveal".*?</div></div>','',s)
 file=D/path.strip('/')/'index.html';file.parent.mkdir(parents=True,exist_ok=True);file.write_text(s)
# Translation metadata stays specific to each page rather than translating SEO keywords mechanically.
trmeta={
'/en/':('Fotoğraf & Film | Charlot Crea','Charlot Crea ile fotoğraf ve tanıtım filmi. Seçili projeleri, uluslararası çalışmaları ve 25 yılı aşkın fotoğraf deneyimini keşfedin.'),
'/en/photography/':('Fotoğraf Portfolyosu | Charlot Crea','Charlot Crea’nın Türkiye, BAE, Mısır ve Suudi Arabistan’daki seçili fotoğraf projelerini, galerileri ve proje konumlarını keşfedin.'),
'/en/film/':('Tanıtım Filmi Prodüksiyonu | Charlot Crea','Charlot Crea’nın tanıtım filmi yaklaşımını keşfedin. Marka, ürün veya hizmet hikâyeniz için brief, prodüksiyon ve teslim sürecini konuşalım.'),
'/en/about/':('Hakkımızda | Charlot Crea','Charlot Crea’nın fotoğraf yaklaşımını, 25 yılı aşkın deneyimini, dört kıtadaki çalışmalarını ve çekim öncesi ve sonrası danışmanlığını keşfedin.'),
'/en/references/':('Referanslar | Charlot Crea','Accor, Rixos, Fairmont, Swissôtel, Sofitel ve Mövenpick dâhil Charlot Crea portfolyosundan seçili marka referanslarını inceleyin.'),
'/en/services/':('Fotoğraf ve Film Hizmetleri | Charlot Crea','Charlot Crea’nın mimari, portre, moda, doğa ve otel fotoğrafçılığı ile tanıtım filmi hizmetlerini keşfedin.'),
'/en/contact/':('İletişim ve Proje Talebi | Charlot Crea','Fotoğraf veya tanıtım filmi projenizi Charlot Crea ile planlayın. Antalya iletişim bilgilerimize ulaşın; konum, tarih ve yaratıcı brief’inizi paylaşın.'),
'/en/destinations/':('Uluslararası Fotoğraf Projeleri | Charlot Crea','Charlot Crea’nın Türkiye, Birleşik Arap Emirlikleri, Mısır ve Suudi Arabistan’daki seçili fotoğraf projelerini destinasyonlara göre keşfedin.'),
'/en/destinations/uae/dubai/':('Dubai Fotoğraf Çalışmaları | Charlot Crea','25 Hours Dubai, Raffles The Palm ve Rixos The Palm dâhil Charlot Crea’nın Dubai fotoğraf projelerini keşfedin; sıradaki projenizi konuşalım.')}
for country,slug in seo['country_slugs'].items():
 en='/en/destinations/'+slug+'/';ps=[p for p in projects if p[2]==country];name=place(seo['country_names'][country]);trmeta[en]=(name+' Fotoğraf Çalışmaları | Charlot Crea','Charlot Crea’nın '+name+' konumundaki fotoğraf çalışmalarını keşfedin. '+', '.join(dict.fromkeys(place(p[1]) for p in ps))+' projelerinin galerilerini inceleyin ve yeni projenizi konuşalım.')
for p in projects:
 trmeta[seo['project_url'](p)]=(p[0]+' Fotoğraf Projesi | Charlot Crea',p[0]+' için '+place(p[1])+', '+place(seo['country_names'][p[2]])+' konumunda hazırlanan Charlot Crea fotoğraf koleksiyonu. '+str(len(seo['galleries'][str(p[3])]['images']))+' seçili fotoğrafı ve ilgili projeleri inceleyin.')
# Clone the main site into fully translated Turkish pages.
for enpath in metadata:
 original=(D/enpath.strip('/')/'index.html').read_text();translated=translated_html(original)
 file=D/routes[enpath].strip('/')/'index.html';file.parent.mkdir(parents=True,exist_ok=True);file.write_text(translated)
# Shared contact data is sourced from the existing site, with a map for that address.
address=config['address'];map_url='https://www.google.com/maps?q='+quote(address)+'&output=embed';directions='https://www.google.com/maps/dir/?api=1&destination='+quote(address)
def contact_block(lang):
 is_tr=lang=='tr'
 return '<section class="section contact-details"><div><span class="label">'+('İletişim bilgileri' if is_tr else 'Contact details')+'</span><h2>'+('Birlikte<br>konuşalım.' if is_tr else 'Let’s start<br>a conversation.')+'</h2></div><div class="contact-channels"><div><span class="label">'+('Telefon' if is_tr else 'Phone')+'</span><a href="tel:+905413962260">'+config['telephone']+'</a></div><div><span class="label">'+('E-posta' if is_tr else 'Email')+'</span><a href="mailto:'+config['email']+'">'+config['email']+'</a></div><div><span class="label">'+('Adres' if is_tr else 'Address')+'</span><address>'+escape(address)+'</address><a class="text-link" href="'+escape(directions,quote=True)+'" target="_blank" rel="noopener noreferrer">'+('Yol tarifi al ↗' if is_tr else 'Get directions ↗')+'</a></div></div><div class="contact-map"><iframe title="'+('Charlot Crea Antalya adresi haritası' if is_tr else 'Map of Charlot Crea’s Antalya address')+'" src="'+escape(map_url+'&hl='+lang,quote=True)+'" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe><p>'+('Harita adres aramasıyla gösterilir.' if is_tr else 'Map shown using an address search.')+'</p></div></section>'
def footer(lang):
 is_tr=lang=='tr';prefix='/'+lang+'/'
 return '<footer class="site-footer"><div class="footer-main"><div class="footer-brand"><a href="'+prefix+'"><img src="/assets/logo.png" alt="Charlot Crea" width="104" height="76" loading="lazy"></a><p>'+('Fotoğraf & Film<br>Türkiye merkezli. Uluslararası çalışmalar.' if is_tr else 'Photography & Film<br>Based in Türkiye. Working internationally.')+'</p></div><div class="footer-contact"><span class="label">'+('İletişim' if is_tr else 'Contact')+'</span><a href="mailto:'+config['email']+'">'+config['email']+'</a><a href="tel:+905413962260">'+config['telephone']+'</a><address>Muratpaşa, Antalya / Türkiye</address><a class="text-link" href="'+('/tr/iletisim/' if is_tr else '/en/contact/')+'">'+('Adres ve harita ↗' if is_tr else 'Address & map ↗')+'</a></div><div class="footer-explore"><span class="label">'+('Keşfedin' if is_tr else 'Explore')+'</span><a href="'+('/tr/fotograf/' if is_tr else '/en/photography/')+'">'+('Fotoğraf' if is_tr else 'Photography')+'</a><a href="'+prefix+'film/">Film</a><a href="'+('/tr/hakkimizda/' if is_tr else '/en/about/')+'">'+('Hakkımızda' if is_tr else 'About')+'</a><a href="'+prefix+'blog/">Blog ↗</a></div></div><div class="footer-bottom"><span>© 2026 Charlot Crea</span><span>'+('Özel demo · Form talepleri gönderilmez' if is_tr else 'Private preview · Form enquiries are not sent')+'</span></div></footer>'
def schema_tr(value):
 if isinstance(value,list):return [schema_tr(v) for v in value]
 if isinstance(value,dict):return {k:('tr' if k=='inLanguage' and isinstance(v,str) else schema_tr(v)) for k,v in value.items()}
 if isinstance(value,str):return tr(url_tr(value))
 return value
tr_header = re.search(r'<header>.*?</header>', (D/'tr/film/index.html').read_text(), re.S).group(0)
# Process all 54 canonical pages, with matching alternates and localised metadata.
allpaths=list(metadata)+[p for p in blog_records if p.startswith('/en/')]+list(reverse)
# reverse includes TR counterpart of every main/blog page.
for path in allpaths:
 lang='tr' if path.startswith('/tr/') else 'en';enpath=reverse[path] if lang=='tr' else path
 file=D/path.strip('/')/'index.html';s=file.read_text();isblog=path in blog_records
 if isblog:
  record=blog_records[path];title=record['title'];desc=record['description'];image=record['image']
  seed=graph_of(template);org=copy.deepcopy(next(n for n in seed if n['@type']=='Organization'));web=copy.deepcopy(next(n for n in seed if n['@type']=='WebSite'))
  crumbs=[('Ana sayfa' if lang=='tr' else 'Home','/'+lang+'/'),('Blog','/'+lang+'/blog/')]
  if record['kind']=='BlogPosting':crumbs.append((record['post'][lang]['title'],path))
  crumb={'@type':'BreadcrumbList','@id':base+path+'#breadcrumb','itemListElement':[{'@type':'ListItem','position':i+1,'name':n,'item':base+u} for i,(n,u) in enumerate(crumbs)]}
  wp={'@type':'CollectionPage' if record['kind']=='Blog' else 'WebPage','@id':base+path+'#webpage','url':base+path,'name':title,'description':desc,'inLanguage':lang,'isPartOf':{'@id':web['@id']},'publisher':{'@id':org['@id']},'breadcrumb':{'@id':crumb['@id']},'primaryImageOfPage':{'@type':'ImageObject','url':base+image}}
  graph=[org,web,wp,crumb]
  if record['kind']=='BlogPosting':
   d=record['post'][lang];article={'@type':'BlogPosting','@id':base+path+'#article','url':base+path,'headline':d['title'],'description':desc,'image':[base+image],'datePublished':'2026-10-31','dateModified':'2026-10-31','inLanguage':lang,'author':{'@id':org['@id']},'publisher':{'@id':org['@id']},'mainEntityOfPage':{'@id':wp['@id']},'articleSection':d['category'],'articleBody':'\n\n'.join(h+'\n'+p for h,p in d['sections'])};graph.append(article);wp['mainEntity']={'@id':article['@id']}
  else:
   listing={'@type':'ItemList','@id':base+path+'#articles','itemListElement':[{'@type':'ListItem','position':i+1,'name':post[lang]['title'],'url':base+'/'+lang+'/blog/'+post[lang]['slug']+'/'} for i,post in enumerate(posts)]};graph.append(listing);wp['mainEntity']={'@id':listing['@id']}
 else:
  if lang=='tr':title,desc=trmeta[enpath];image=metadata[enpath][2];graph=schema_tr(graph_of((D/enpath.strip('/')/'index.html').read_text()))
  else:title,desc,image,*_=metadata[path];graph=graph_of(s)
  wp=next(n for n in graph if n['@id']==base+path+'#webpage');wp['name']=title;wp['description']=desc
  for node in graph:
   if node['@type']=='Service':node['description']=desc
 for node in graph:
  if node['@type']=='Organization':
   node['url']=base+'/en/';node['email']=config['email'];node['address'].update({'streetAddress':config['street_address'],'addressRegion':'Antalya','addressLocality':'Muratpaşa'})
  if node['@type']=='WebSite':node['url']=base+'/en/';node['inLanguage']=['en','tr']
 s=re.sub(r'<html lang="[^"]+"',f'<html lang="{lang}"',s)
 # Turkish blog pages use the same localised site shell as the rest of the site.
 if isblog and lang=='tr':
  header=tr_header
  s=re.sub(r'<header>.*?</header>',lambda m:header,s,count=1,flags=re.S)
 if enpath=='/en/contact/':
  # Replace the earlier compact phone block with full sourced contact details.
  s=re.sub(r'<section class="section" style="padding-top:0;padding-bottom:30px">.*?</section>','',s,flags=re.S)
  s=s.replace('<section class="form-shell">',contact_block(lang)+'<section class="form-shell">',1)
 s=re.sub(r'<footer\b.*?</footer>',lambda m:footer(lang),s,flags=re.S)
 switch='<div class="language-switch" role="group" aria-label="'+('Dil seçimi' if lang=='tr' else 'Choose language')+'">'
 for code,target in [('tr',routes[enpath]),('en',enpath)]:switch+=f'<a href="{target}" lang="{code}" hreflang="{code}"'+(' aria-current="page"' if code==lang else '')+'>'+code.upper()+'</a>'
 switch+='</div>'
 s=s.replace('</nav><button class="mobile-toggle"','</nav>'+switch+'<button class="mobile-toggle"',1)
 if lang=='tr':s=s.replace('class="active" aria-current="page"','class="active" aria-current="page"')
 s=meta_head(s,lang,path,title,desc,image,graph,isblog and blog_records[path]['kind']=='BlogPosting')
 s=s.replace('</body>','<script src="/site.js"></script></body>')
 file.write_text(s)
# Regenerate the sitemap including reciprocal language alternatives and blog pages.
NS=seo['NS'];IM=seo['IM'];XH='http://www.w3.org/1999/xhtml'
ET.register_namespace('',NS);ET.register_namespace('image',IM);ET.register_namespace('xhtml',XH)
root=ET.Element('{'+NS+'}urlset')
for path in allpaths:
 node=ET.SubElement(root,'{'+NS+'}url');ET.SubElement(node,'{'+NS+'}loc').text=base+path
 enpath=reverse[path] if path.startswith('/tr/') else path
 for lang,url in [('en',enpath),('tr',routes[enpath]),('x-default',enpath)]:ET.SubElement(node,'{'+XH+'}link',{'rel':'alternate','hreflang':lang,'href':base+url})
 if path in blog_records and blog_records[path]['kind']=='BlogPosting':ET.SubElement(node,'{'+NS+'}lastmod').text='2026-10-31'
 media=seo['Media']();media.feed((D/path.strip('/')/'index.html').read_text())
 for image,alt in dict.fromkeys(media.images):
  im=ET.SubElement(node,'{'+IM+'}image');ET.SubElement(im,'{'+IM+'}loc').text=base+image
ET.ElementTree(root).write(D/'sitemap.xml',encoding='utf-8',xml_declaration=True)
# Redirect explicit /index.html variants for new language and editorial pages too.
redirects=json.loads((D/'redirects.json').read_text())
for path in allpaths:redirects[path+'index.html']=path
(D/'redirects.json').write_text(json.dumps(redirects,ensure_ascii=False,indent=2))
(D/'_redirects').write_text('\n'.join(f'{a} {b} 301!' for a,b in redirects.items())+'\n')
htaccess=(D/'.htaccess').read_text()
for path in allpaths:
 if path+'index.html' not in seo['redirects']:htaccess+='RewriteRule ^'+re.escape((path+'index.html').lstrip('/'))+' '+path+' [R=301,L,NE]\n'
(D/'.htaccess').write_text(htaccess)
(D/'build-info.json').write_text(json.dumps({'environment':'production' if production else 'preview','site_url':base,'canonical_pages':len(allpaths),'languages':['en','tr'],'blog_posts':5},indent=2))
Path('data/language-routes.json').write_text(json.dumps(routes,ensure_ascii=False,indent=2))
print(f'Languages: {len(allpaths)} pages; 5 blog articles in EN + TR.')

import runpy
runpy.run_path("polish.py")
