"""Publish the reviewed Drive portfolio and connect photographs, films and destinations."""
from pathlib import Path
from html import escape,unescape
from urllib.parse import urlencode
import json,re,copy,xml.etree.ElementTree as ET
from site_pages import schema,graph,rewrite_head,breadcrumb
D=Path('dist');info=json.loads((D/'build-info.json').read_text());base=info['site_url'];catalogue=json.loads(Path('data/portfolio.json').read_text());projects=catalogue['projects'];films=catalogue['films'];cities=json.loads(Path('data/portfolio-cities.json').read_text());routes=json.loads(Path('data/language-routes.json').read_text());original_routes=dict(routes)
country_slugs={'Türkiye':'turkey','UAE':'uae','Egypt':'egypt','Saudi Arabia':'saudi-arabia'}
country_tr_slugs={'turkey':'turkiye','uae':'bae','egypt':'misir','saudi-arabia':'suudi-arabistan'}
place_tr={'Istanbul':'İstanbul','Cairo':'Kahire','Luxor':'Luksor','Aswan':'Asvan','Makkah':'Mekke','Saudi Arabia':'Suudi Arabistan','Egypt':'Mısır','UAE':'BAE','International':'Uluslararası','Selected properties':'Seçili tesisler','Selected spaces':'Seçili mekânlar'}
def loc(text,lang):return place_tr.get(text,text) if lang=='tr' else text
for p in projects:routes['/en/projects/'+p['slug']+'/']='/tr/projeler/'+p['slug']+'/'
for f in films:routes['/en/films/'+f['slug']+'/']='/tr/filmler/'+f['slug']+'/'
for c in cities:routes['/en/destinations/'+c['country_slug']+'/'+c['slug']+'/']='/tr/destinasyonlar/'+country_tr_slugs[c['country_slug']]+'/'+c['slug']+'/'
reverse={v:k for k,v in routes.items()}
templates={lang:(D/lang/('film/index.html')).read_text() for lang in ('en','tr')}
def route(en,lang):return routes[en] if lang=='tr' else en
def pp(p,lang):return route('/en/projects/'+p['slug']+'/',lang)
def fp(f,lang):return route('/en/films/'+f['slug']+'/',lang)
def country_path(country,lang):
 if country not in country_slugs:return '/tr/destinasyonlar/' if lang=='tr' else '/en/destinations/'
 return route('/en/destinations/'+country_slugs[country]+'/',lang)
def city_path(country,city,lang):
 if city=='Dubai':return route('/en/destinations/uae/dubai/',lang)
 c=next((x for x in cities if x['country']==country and x['key']==city),None)
 return route('/en/destinations/'+c['country_slug']+'/'+c['slug']+'/',lang) if c else None

def img(asset,alt,loading='lazy',cls=''):
 if isinstance(asset,str):return f'<img src="{asset}" alt="{escape(alt,quote=True)}" loading="{loading}" decoding="async"'+(' class="'+cls+'"' if cls else '')+'>'
 src=asset['small'];full=asset['src'];setattrs=f' srcset="{src} 800w, {full} {asset["width"]}w" sizes="(max-width:760px) 90vw, 45vw"' if src!=full else ''
 return f'<img src="{src}"{setattrs} width="{asset["width"]}" height="{asset["height"]}" alt="{escape(alt,quote=True)}" loading="{loading}" decoding="async">'

def photo_card(p,lang):
 label=p['name'] if p['slug']!='architecture-interiors' or lang=='en' else 'Mimari & İç Mekân'
 return f'<a class="project-card" href="{pp(p,lang)}" data-country="{p["country"]}"><div class="picture">'+img({'src':p['cover'],'small':p['cover_small'],'width':p['cover_width'],'height':p['cover_height']},label)+'</div><div class="project-info"><div><h3>'+escape(label)+'</h3><p>'+escape(loc(p['city'],lang))+' · '+('Koleksiyon' if p['collection'] and lang=='tr' else 'Collection' if p['collection'] else escape(loc(p['country'],lang)))+'</p></div><span class="arrow">↗</span></div></a>'

def video_button(f,lang,hero=False):
 data='data-video-src="'+f['file']+'"' if f.get('file') else 'data-drive-id="'+f['drive_id']+'"'
 title='Filmi oynat: '+f['name'] if lang=='tr' else 'Play film: '+f['name']
 return '<button type="button" class="film-poster'+(' film-feature-poster' if hero else '')+'" '+data+' data-film-title="'+escape(f['name'],quote=True)+'" aria-label="'+escape(title,quote=True)+'">'+img(f['poster'],f['name'],'eager' if hero else 'lazy')+'<span class="film-play" aria-hidden="true">▶</span><span class="film-watch-label">'+('Filmi izle' if lang=='tr' else 'Watch film')+'</span></button>'

def film_card(f,lang):return '<article class="film-card" data-country="'+f['country']+'">'+video_button(f,lang)+'<div class="project-info"><div><h3><a href="'+fp(f,lang)+'">'+escape(f['name'])+'</a></h3><p>'+escape((loc(f['city'],lang) if f['city'] else ('Showreel' if f['country']=='International' else loc(f['country'],lang)))+(' · Türkçe anlatım' if f.get('audio_language')=='tr' and lang=='tr' else ' · Turkish narration' if f.get('audio_language')=='tr' else ''))+'</p></div><a class="arrow" href="'+fp(f,lang)+'" aria-label="'+escape(f['name'],quote=True)+'">↗</a></div></article>'

def filters(items,lang):
 choices=['All']+[c for c in ['Türkiye','UAE','Egypt','Saudi Arabia','International'] if any(p['country']==c for p in items)]
 return '<div class="filters" aria-label="'+('Ülkeye göre filtrele' if lang=='tr' else 'Filter by country')+'">'+''.join('<button type="button" data-filter="'+c+'" class="'+('selected' if c=='All' else '')+'" aria-pressed="'+('true' if c=='All' else 'false')+'">'+('Tümü' if c=='All' and lang=='tr' else loc(c,lang))+'</button>' for c in choices)+'</div>'

def dialog(lang):return '<dialog class="film-dialog" aria-label="'+('Film oynatıcı' if lang=='tr' else 'Film player')+'"><button class="film-close" aria-label="'+('Filmi kapat' if lang=='tr' else 'Close film')+'">×</button><div class="film-player-host"></div><div class="film-player-caption" aria-live="polite"></div></dialog>'
def gallery_dialog(lang):return '<dialog class="gallery-dialog" aria-label="'+('Fotoğraf görüntüleyici' if lang=='tr' else 'Photo viewer')+'"><button class="gallery-close" aria-label="'+('Fotoğrafı kapat' if lang=='tr' else 'Close photo')+'">×</button><button class="gallery-prev" aria-label="'+('Önceki fotoğraf' if lang=='tr' else 'Previous photograph')+'">←</button><img alt=""><button class="gallery-next" aria-label="'+('Sonraki fotoğraf' if lang=='tr' else 'Next photograph')+'">→</button><p class="gallery-count" aria-live="polite"></p></dialog>'
def cta(lang):return '<section class="cta"><span class="label">'+('Sıradaki projeniz' if lang=='tr' else 'Your next project')+'</span><h2>'+('Hikâyenizi birlikte<br>şekillendirelim.' if lang=='tr' else 'Let’s shape<br>your next story.')+'</h2><p>'+('Fotoğraf veya film projeniz için konumu, vizyonunuzu ve tarihleri paylaşın.' if lang=='tr' else 'Share the location, vision and timing for your photography or film project.')+'</p><a class="btn" href="'+('/tr/iletisim/' if lang=='tr' else '/en/contact/')+'">'+('Projenizi konuşalım ↗' if lang=='tr' else 'Discuss a project ↗')+'</a></section>'

def publish(path,lang,title,description,body,kind,crumbs,image='/assets/hero.jpg',extra=None,main_class=''):
 template=templates[lang];s=re.sub(r'<main>.*?</main>',lambda m:'<main'+(' class="'+main_class+'"' if main_class else '')+'>'+body+'</main>',template,flags=re.S)
 # A new page receives its own schema rather than inheriting the template film's entities.
 nodes=schema(base,path,title,description,kind,crumbs,template,image)
 if extra:extra(nodes)
 en=reverse[path] if lang=='tr' else path;alternate=en if lang=='tr' else routes[en]
 s=rewrite_head(s,base,path,alternate,title,description,nodes)
 # Set the primary navigation state for the new page.
 s=re.sub(r' class="active" aria-current="page"','',s)
 nav_target=('/tr/film/' if lang=='tr' else '/en/film/') if ('/films/' in en or en=='/en/film/') else ('/tr/destinasyonlar/' if lang=='tr' else '/en/destinations/') if '/destinations/' in en else ('/tr/fotograf/' if lang=='tr' else '/en/photography/')
 s=s.replace('<a href="'+nav_target+'">','<a href="'+nav_target+'" class="active" aria-current="page">',1)
 f=D/path.strip('/')/'index.html';f.parent.mkdir(parents=True,exist_ok=True);f.write_text(s)

def video_schema(f,lang,path):
 d={'@type':'VideoObject','@id':base+fp(f,lang)+'#video','name':f['name'],'description':('Charlot Crea portfolyosundan '+f['name']+' filmi.' if lang=='tr' else f['name']+' film from the Charlot Crea portfolio.'),'thumbnailUrl':[base+f['poster']],'uploadDate':f['published'],'embedUrl':f['embed_url'],'publisher':{'@id':base+'/#organization'},'inLanguage':lang}
 if not f.get('audio_language'):d.pop('inLanguage',None)
 else:d['inLanguage']=f['audio_language']
 if f.get('file'):d['contentUrl']=base+f['file']
 if f.get('duration'):d['duration']=f['duration']
 return d

for lang in ('en','tr'):
 home=('/tr/' if lang=='tr' else '/en/');photography=('/tr/fotograf/' if lang=='tr' else '/en/photography/');filmroute='/'+lang+'/film/';home_label='Ana sayfa' if lang=='tr' else 'Home';photo_label='Fotoğraf' if lang=='tr' else 'Photography'
 # Every photographic collection, including the existing canonical project URLs.
 for p in projects:
  path=pp(p,lang);name='Mimari & İç Mekân' if lang=='tr' and p['slug']=='architecture-interiors' else p['name'];place=loc(p['city'],lang);title=name+(' Fotoğraf Koleksiyonu | Charlot Crea' if lang=='tr' else ' Photography | Charlot Crea');desc=p['description'][lang]+' '+(str(len(p['images']))+' fotoğrafı inceleyin ve ilgili çalışmaları keşfedin.' if lang=='tr' else 'Explore '+str(len(p['images']))+' photographs and related work.');crumbs=[(home_label,home),(photo_label,photography),(name,path)]
  body=breadcrumb(crumbs,lang)+'<section class="page-intro"><span class="label">'+escape(place)+'</span><h1>'+escape(name)+'</h1><p>'+escape(p['description'][lang])+'</p><div class="work-meta"><span>'+photo_label+'</span><span>'+str(len(p['images']))+(' fotoğraf' if lang=='tr' else ' photographs')+'</span></div></section><section class="section full-project work-gallery-section"><div class="photo-gallery" aria-label="'+escape(name,quote=True)+'">'
  for i,asset in enumerate(p['images']):body+='<a class="gallery-open" data-gallery="'+str(i)+'" href="'+asset['src']+'" aria-label="'+escape(name,quote=True)+' '+str(i+1)+'">'+img(asset,name+(' — fotoğraf ' if lang=='tr' else ' — photograph ')+str(i+1))+'</a>'
  body+='</div></section>'
  related=[f for f in films if f.get('photo_slug')==p['slug']]
  if related:body+='<section class="section"><div class="section-head"><h2>'+('Aynı hikâye, hareketle.' if lang=='tr' else 'The story in motion.')+'</h2></div><div class="film-grid">'+''.join(film_card(f,lang) for f in related)+'</div></section>'
  citylinks=[(loc(c,lang),city_path(p['country'],c,lang)) for c in p['cities']];body+='<section class="section project-navigation"><a class="text-link" href="'+country_path(p['country'],lang)+'">'+('Destinasyonu keşfedin ↗' if lang=='tr' else 'Explore the destination ↗')+'</a>'+''.join('<a class="text-link" href="'+url+'">'+escape(n)+' ↗</a>' for n,url in citylinks if url)+'</section>'+cta(lang)+gallery_dialog(lang)+(dialog(lang) if related else '')
  def photo_extra(nodes,p=p,related=related,path=path,lang=lang):
   photo_nodes=[{'@type':'ImageObject','@id':base+path+'#photo-'+str(i+1),'contentUrl':base+a['src'],'thumbnailUrl':base+a['small'],'name':p['name']+(' — fotoğraf ' if lang=='tr' else ' — photograph ')+str(i+1),'width':a['width'],'height':a['height'],'creator':{'@id':base+'/#organization'},'creditText':'Charlot Crea'} for i,a in enumerate(p['images'])]
   nodes[2]['hasPart']=[{'@id':n['@id']} for n in photo_nodes];nodes[2]['contentLocation']={'@type':'Place','name':p['city']};nodes.extend(photo_nodes)
   for f in related:nodes.append(video_schema(f,lang,path))
  publish(path,lang,title,desc,body,'ImageGallery',crumbs,p['cover'],photo_extra)
 # Photography overview is compact at every image ratio.
 path=photography;title=('Fotoğraf Portfolyosu | Charlot Crea' if lang=='tr' else 'Photography Portfolio | Charlot Crea');desc=('Mimari, yaşam tarzı ve insan odaklı fotoğraflarla '+str(len(projects))+' seçili koleksiyonu keşfedin.' if lang=='tr' else 'Explore '+str(len(projects))+' selected photography collections, from architecture and interiors to people and lifestyle.');crumbs=[(home_label,home),(photo_label,path)]
 ordered=sorted(projects,key=lambda p:(catalogue['featured_projects'].index(p['slug']) if p['slug'] in catalogue['featured_projects'] else 99))
 body=breadcrumb(crumbs,lang)+'<section class="page-intro"><span class="label">'+('Fotoğraf / Seçili koleksiyonlar' if lang=='tr' else 'Photography / Selected collections')+'</span><h1>'+('Mekânlar.<br>İnsanlar. Hikâyeler.' if lang=='tr' else 'Places.<br>People. Stories.')+'</h1><p>'+escape(desc)+'</p><div class="work-meta"><span>'+str(len(projects))+(' koleksiyon' if lang=='tr' else ' collections')+'</span><span>'+str(len({a['src'] for p in projects for a in p['images']}))+(' fotoğraf' if lang=='tr' else ' photographs')+'</span></div></section><section class="section work-overview">'+filters(projects,lang)+'<div class="project-grid">'+''.join(photo_card(p,lang) for p in ordered)+'</div></section>'+cta(lang)
 def listing(nodes,items=ordered,lang=lang,path=path):
  lid=base+path+'#projects';nodes[2]['mainEntity']={'@id':lid};nodes.append({'@type':'ItemList','@id':lid,'itemListElement':[{'@type':'ListItem','position':i+1,'name':p['name'],'url':base+pp(p,lang)} for i,p in enumerate(items)]})
 publish(path,lang,title,desc,body,'CollectionPage',crumbs,ordered[0]['cover'],listing,'photography-page')
 # Film index with a real showreel and project playback.
 path=filmroute;title=('Film Portfolyosu ve Showreel | Charlot Crea' if lang=='tr' else 'Film Portfolio & Showreel | Charlot Crea');desc=(str(len(films))+' filmden oluşan Charlot Crea seçkisini keşfedin. Showreel, otel filmleri ve ilgili fotoğraf projelerini izleyin.' if lang=='tr' else 'Watch '+str(len(films))+' selected films by Charlot Crea, including the showreel, hotel films and related photography collections.');crumbs=[(home_label,home),('Film',path)];featured=films[0]
 body=breadcrumb(crumbs,lang)+'<section class="page-intro"><span class="label">Film / Showreel</span><h1>'+('Hikâyeler.<br>Hareket hâlinde.' if lang=='tr' else 'Stories.<br>In motion.')+'</h1><p>'+escape(desc)+'</p></section><section class="section film-feature">'+video_button(featured,lang,True)+'<div class="work-meta"><span>Charlot Crea — Showreel</span><a class="text-link" href="'+fp(featured,lang)+'">'+('Filmi keşfedin ↗' if lang=='tr' else 'Explore the film ↗')+'</a></div></section><section class="section"><div class="section-head"><div><span class="label">'+('Film seçkisi' if lang=='tr' else 'Selected films')+'</span><h2>'+('Bir yer.<br>Bir hikâye.' if lang=='tr' else 'A place.<br>A story.')+'</h2></div></div>'+filters(films[1:],lang)+'<div class="film-grid">'+''.join(film_card(f,lang) for f in films[1:])+'</div></section>'+cta(lang)+dialog(lang)
 def film_listing(nodes,lang=lang,path=path):
  nodes[2]['mainEntity']={'@id':base+path+'#films'};nodes.append({'@type':'ItemList','@id':base+path+'#films','itemListElement':[{'@type':'ListItem','position':i+1,'name':f['name'],'url':base+fp(f,lang)} for i,f in enumerate(films)]})
  nodes.append(video_schema(films[0],lang,path))
 publish(path,lang,title,desc,body,'CollectionPage',crumbs,featured['poster'],film_listing)
 # Dedicated film pages have stable slugs, VideoObject data and reciprocal project links.
 for f in films:
  path=fp(f,lang);name=f['name'];title=name+(' Filmi | Charlot Crea' if lang=='tr' else ' Film | Charlot Crea');desc=(name+' filmini Charlot Crea portfolyosunda izleyin. İlgili fotoğraf ve destinasyon çalışmalarını keşfedin.' if lang=='tr' else 'Watch the '+name+' film from the Charlot Crea portfolio and explore related photography and destination work.');crumbs=[(home_label,home),('Film',filmroute),(name,path)]
  body=breadcrumb(crumbs,lang)+'<section class="page-intro"><span class="label">Film'+(' / '+escape(loc(f['city'],lang)) if f['city'] else '')+'</span><h1>'+escape(name)+'</h1><p>'+escape(desc.split(' İlgili')[0] if lang=='tr' else 'A selected film from the Charlot Crea portfolio.')+'</p></section><section class="section film-feature">'+video_button(f,lang,True)+'</section><section class="section film-project-links">'
  if f['photo_slug']:
   p=next(p for p in projects if p['slug']==f['photo_slug']);body+='<a class="text-link" href="'+pp(p,lang)+'">'+('Fotoğraf koleksiyonunu keşfedin ↗' if lang=='tr' else 'Explore the photography collection ↗')+'</a>'
  if f['country']!='International':body+='<a class="text-link" href="'+country_path(f['country'],lang)+'">'+escape(loc(f['country'],lang))+' ↗</a>'
  city=city_path(f['country'],f['city'],lang)
  if city:body+='<a class="text-link" href="'+city+'">'+escape(loc(f['city'],lang))+' ↗</a>'
  body+='</section>'+cta(lang)+dialog(lang)
  def one_video(nodes,f=f,lang=lang,path=path):
   v=video_schema(f,lang,path);v['mainEntityOfPage']={'@id':base+path+'#webpage'};nodes[2]['mainEntity']={'@id':v['@id']};nodes.append(v)
  publish(path,lang,title,desc,body,'ItemPage',crumbs,f['poster'],one_video)
 # Country pages gather both media types and lead to substantiated city collections.
 for country,slug in country_slugs.items():
  path=route('/en/destinations/'+slug+'/',lang);file=D/path.strip('/')/'index.html';s=file.read_text();ps=[p for p in projects if p['country']==country];fs=[f for f in films if f['country']==country]
  block='<section class="section destination-work"><div class="section-head"><div><span class="label">'+escape(loc(country,lang))+'</span><h2>'+('Fotoğraf koleksiyonları' if lang=='tr' else 'Photography collections')+'</h2></div><span class="work-count">'+str(len(ps))+(' koleksiyon' if lang=='tr' else ' collections')+'</span></div><div class="project-grid">'+''.join(photo_card(p,lang) for p in ps)+'</div></section>'
  s=re.sub(r'<section class="section" style="padding-top:20px"><div class="project-grid">.*?</section>',lambda m:block,s,count=1,flags=re.S)
  more=''
  if fs:more+='<section class="section destination-films"><div class="section-head"><h2>'+('Bu destinasyondan filmler' if lang=='tr' else 'Films from this destination')+'</h2></div><div class="film-grid">'+''.join(film_card(f,lang) for f in fs)+'</div></section>'+dialog(lang)
  localcities=[c for c in cities if c['country']==country]
  if country=='UAE':localcities=[{'country_slug':'uae','slug':'dubai','en':{'name':'Dubai'},'tr':{'name':'Dubai'}}]+localcities
  if localcities:
   more+='<section class="section city-navigation"><span class="label">'+('Şehirlere göre keşfedin' if lang=='tr' else 'Explore by city')+'</span><div class="city-link-grid">'+''.join('<a href="'+route('/en/destinations/'+c['country_slug']+'/'+c['slug']+'/',lang)+'"><span>'+escape(c[lang]['name'])+'</span><span>↗</span></a>' for c in localcities)+'</div></section>'
  s=s.replace('<section class="cta">',more+'<section class="cta">',1)
  nodes=graph(s);wp=next(n for n in nodes if n['@id']==base+path+'#webpage');oldlist=next((n for n in nodes if n['@type']=='ItemList'),None)
  if oldlist:oldlist['itemListElement']=[{'@type':'ListItem','position':i+1,'name':p['name'],'url':base+pp(p,lang)} for i,p in enumerate(ps)]
  for f in fs:nodes.append(video_schema(f,lang,path))
  en=reverse[path] if lang=='tr' else path;s=rewrite_head(s,base,path,en if lang=='tr' else routes[en],wp['name'],wp['description'],nodes);file.write_text(s)
 # Dubai keeps its editorial context while its portfolio expands to five properties.
 path=route('/en/destinations/uae/dubai/',lang);file=D/path.strip('/')/'index.html';s=file.read_text();ps=[p for p in projects if p['country']=='UAE' and 'Dubai' in p['cities']]
 s=re.sub(r'<div class="project-grid">.*?</div></section>',lambda m:'<div class="project-grid">'+''.join(photo_card(p,lang) for p in ps)+'</div></section>',s,count=1,flags=re.S)
 nodes=graph(s);listing_node=next((n for n in nodes if n['@type']=='ItemList'),None)
 if listing_node:listing_node['itemListElement']=[{'@type':'ListItem','position':i+1,'name':p['name'],'url':base+pp(p,lang)} for i,p in enumerate(ps)]
 wp=next(n for n in nodes if n['@id']==base+path+'#webpage');en=reverse[path] if lang=='tr' else path;s=rewrite_head(s,base,path,en if lang=='tr' else routes[en],wp['name'],wp['description'],nodes);file.write_text(s)
 # Dedicated city pages exist only where an identified project or film supports the location.
 for city in cities:
  en='/en/destinations/'+city['country_slug']+'/'+city['slug']+'/';path=route(en,lang);name=city[lang]['name'];ps=[p for p in projects if p['country']==city['country'] and city['key'] in p['cities']];fs=[f for f in films if f['country']==city['country'] and f['city']==city['key']]
  assert ps or fs,city['key'];country=country_path(city['country'],lang);label=loc(city['country'],lang);title=name+(' Fotoğraf ve Film Çalışmaları | Charlot Crea' if lang=='tr' else ' Photography & Film | Charlot Crea');desc=(name+' için '+str(len(ps))+' fotoğraf koleksiyonu ve '+str(len(fs))+' film. Charlot Crea’nın projelerini keşfedin ve çekiminizi planlayın.' if lang=='tr' else 'Explore '+str(len(ps))+' photography collections and '+str(len(fs))+' films from '+name+' in the Charlot Crea portfolio. Discover the work and plan your project.');crumbs=[(home_label,home),('Destinasyonlar' if lang=='tr' else 'Destinations',route('/en/destinations/',lang)),(label,country),(name,path)]
  body=breadcrumb(crumbs,lang)+'<section class="destination-opening"><span class="destination-bg-text" aria-hidden="true">'+escape(name.upper())+'</span><div class="destination-opening-content"><span class="label">'+escape(label)+'</span><h1>'+escape(name)+(' — fotoğraf ve film' if lang=='tr' else ' in stills & motion.')+'</h1><p>'+escape(city[lang]['intro'])+'</p></div></section>'
  if ps:body+='<section class="section"><div class="section-head"><h2>'+('Fotoğraf koleksiyonları' if lang=='tr' else 'Photography collections')+'</h2></div><div class="project-grid">'+''.join(photo_card(p,lang) for p in ps)+'</div></section>'
  if fs:body+='<section class="section"><div class="section-head"><h2>'+('Film seçkisi' if lang=='tr' else 'Selected films')+'</h2></div><div class="film-grid">'+''.join(film_card(f,lang) for f in fs)+'</div></section>'
  body+='<section class="section city-brief"><div><span class="label">'+('Projenizi planlayın' if lang=='tr' else 'Plan your project')+'</span><h2>'+('Mekânınızın<br>hikâyesini anlatın.' if lang=='tr' else 'Tell the story<br>of your place.')+'</h2></div><div class="bodytext"><p>'+('Konumu, hedef kitlenizi ve fotoğraf veya film için önceliklerinizi paylaşın. Kullanılacak kanallar, görsel referanslar ve tercih edilen tarihler, yaratıcı yaklaşımı ve prodüksiyon kapsamını birlikte planlamamıza yardımcı olur.' if lang=='tr' else 'Share the location, your audience and the priorities for photography or film. Intended channels, visual references and preferred dates help shape the creative approach and production scope together.')+'</p><a class="text-link" href="'+country+'">'+escape(label)+(' çalışmalarına dön ↗' if lang=='tr' else ' portfolio ↗')+'</a></div></section>'+cta(lang)+(dialog(lang) if fs else '')
  def city_extra(nodes,ps=ps,fs=fs,city=city,path=path,lang=lang):
   place={'@type':'City','@id':base+path+'#city','name':city[lang]['name'],'containedInPlace':{'@type':'Country','name':loc(city['country'],lang)}};nodes[2]['about']={'@id':place['@id']};nodes.append(place)
   items=[(p['name'],pp(p,lang)) for p in ps]+[(f['name'],fp(f,lang)) for f in fs];lid=base+path+'#work';nodes[2]['mainEntity']={'@id':lid};nodes.append({'@type':'ItemList','@id':lid,'itemListElement':[{'@type':'ListItem','position':i+1,'name':n,'url':base+u} for i,(n,u) in enumerate(items)]})
   for f in fs:nodes.append(video_schema(f,lang,path))
  image=ps[0]['cover'] if ps else fs[0]['poster'];publish(path,lang,title,desc,body,'CollectionPage',crumbs,image,city_extra,'destination-page')
 # Destination index becomes a useful country-to-city-to-work overview.
 path=route('/en/destinations/',lang);title=('Destinasyonlar | Fotoğraf ve Film | Charlot Crea' if lang=='tr' else 'Destinations | Photography & Film | Charlot Crea');desc=('Türkiye, BAE, Mısır ve Suudi Arabistan’daki gerçek proje ve filmlerle ülke ve şehirlere göre portfolyoyu keşfedin.' if lang=='tr' else 'Explore the Charlot Crea portfolio by country and city, with photography and films from Türkiye, the UAE, Egypt and Saudi Arabia.');crumbs=[(home_label,home),('Destinasyonlar' if lang=='tr' else 'Destinations',path)]
 body=breadcrumb(crumbs,lang)+'<section class="page-intro"><span class="label">'+('Bir yer. Bir bakış açısı.' if lang=='tr' else 'A place. A point of view.')+'</span><h1>'+('Farklı yerler.<br>Birbirine bağlanan hikâyeler.' if lang=='tr' else 'Different places.<br>Connected stories.')+'</h1><p>'+escape(desc)+'</p></section><section class="section destination-index-grid">'
 for country,slug in country_slugs.items():
  ps=[p for p in projects if p['country']==country];fs=[f for f in films if f['country']==country];cover=next((p['cover_small'] for p in ps if p['slug'] in catalogue['featured_projects']),ps[0]['cover_small']);target=country_path(country,lang)
  body+='<article class="destination-country-card"><a href="'+target+'" class="destination-cover">'+img(cover,loc(country,lang))+'</a><div class="section-head"><h2><a href="'+target+'">'+escape(loc(country,lang))+' ↗</a></h2></div><p>'+str(len(ps))+(' fotoğraf koleksiyonu' if lang=='tr' else ' photography collections')+' · '+str(len(fs))+(' film' if lang=='tr' else ' films')+'</p><div class="destination-city-tags">'
  for c in cities:
   if c['country']==country:body+='<a href="'+route('/en/destinations/'+c['country_slug']+'/'+c['slug']+'/',lang)+'">'+escape(c[lang]['name'])+' ↗</a>'
  if country=='UAE':body+='<a href="'+route('/en/destinations/uae/dubai/',lang)+'">Dubai ↗</a>'
  body+='</div></article>'
 body+='</section>'+cta(lang)
 publish(path,lang,title,desc,body,'CollectionPage',crumbs)
 # Home: a broader photography selection and the actual one-minute showreel.
 file=D/lang/'index.html';s=file.read_text();featured_projects=[next(p for p in projects if p['slug']==slug) for slug in catalogue['featured_projects']]
 s=re.sub(r'<div class="project-grid">.*?</div></section>',lambda m:'<div class="project-grid">'+''.join(photo_card(p,lang) for p in featured_projects)+'</div></section>',s,count=1,flags=re.S)
 showreel='<section class="section home-showreel"><div class="section-head"><div><span class="label">Film / Showreel</span><h2>'+('Bir dakikada<br>birçok hikâye.' if lang=='tr' else 'One minute.<br>Many stories.')+'</h2></div><a class="text-link" href="'+filmroute+'">'+('Tüm filmler ↗' if lang=='tr' else 'All films ↗')+'</a></div>'+video_button(films[0],lang)+'</section>'
 s=s.replace('<section class="section intro">',showreel+'<section class="section intro">',1)
 # Update the existing country rows with cities that now have dedicated work pages.
 for country,slug in country_slugs.items():
  names=[c[lang]['name'] for c in cities if c['country']==country]
  if country=='UAE':names=['Dubai']+names
  if country=='Saudi Arabia':names=['Mekke' if lang=='tr' else 'Makkah']
  url=country_path(country,lang)
  s=re.sub(r'(<a class="destination-row" href="'+re.escape(url)+r'">.*?<p>).*?(</p>)',lambda m:m[1]+escape(' · '.join(names))+m[2],s,flags=re.S)
 s=s.replace('</main>',dialog(lang)+'</main>',1)
 s=s.replace('<main>','<main class="home-page">',1)
 nodes=graph(s);wp=next(n for n in nodes if n['@id']==base+home+'#webpage');nodes.append(video_schema(films[0],lang,home));wp['hasPart']=[{'@id':base+fp(films[0],lang)+'#video'}]
 s=rewrite_head(s,base,home,routes['/en/'] if lang=='en' else '/en/',wp['name'],wp['description'],nodes);file.write_text(s)
# Every existing page loads the portfolio controls; the shared navigation remains unchanged.
for file in D.rglob('index.html'):
 s=file.read_text()
 if '/work.css' not in s:s=s.replace('</head>','<link rel="stylesheet" href="/work.css"></head>')
 if '/work.js' not in s:s=s.replace('</body>','<script src="/work.js"></script></body>')
 file.write_text(s)
# Rebuild the complete canonical sitemap, alternates and video entries from generated pages.
NS='http://www.sitemaps.org/schemas/sitemap/0.9';IM='http://www.google.com/schemas/sitemap-image/1.1';XH='http://www.w3.org/1999/xhtml';VI='http://www.google.com/schemas/sitemap-video/1.1'
for prefix,ns in [('',NS),('image',IM),('xhtml',XH),('video',VI)]:ET.register_namespace(prefix,ns)
root=ET.Element('{'+NS+'}urlset');paths=[]
for file in sorted(D.rglob('index.html')):
 path='/'+str(file.parent.relative_to(D))+'/';s=file.read_text();paths.append(path);lang=path.split('/')[1];en=reverse[path] if lang=='tr' else path
 node=ET.SubElement(root,'{'+NS+'}url');ET.SubElement(node,'{'+NS+'}loc').text=base+path
 for code,target in [('en',en),('tr',routes[en]),('x-default',en)]:ET.SubElement(node,'{'+XH+'}link',{'rel':'alternate','hreflang':code,'href':base+target})
 nodes=graph(s)
 for im in [x for x in nodes if x['@type']=='ImageObject']:
  if im.get('contentUrl'):n=ET.SubElement(node,'{'+IM+'}image');ET.SubElement(n,'{'+IM+'}loc').text=im['contentUrl']
 # Film detail pages each advertise their own video, avoiding duplicate sitemap entries on listings.
 if '/films/' in en:
  for video in [x for x in nodes if x['@type']=='VideoObject']:
   n=ET.SubElement(node,'{'+VI+'}video')
   for tag,value in [('thumbnail_loc',video['thumbnailUrl'][0]),('title',video['name']),('description',video['description']),('player_loc',video['embedUrl'])]:ET.SubElement(n,'{'+VI+'}'+tag).text=value
   if video.get('contentUrl'):ET.SubElement(n,'{'+VI+'}content_loc').text=video['contentUrl']
ET.ElementTree(root).write(D/'sitemap.xml',encoding='utf-8',xml_declaration=True)
redirects=json.loads((D/'redirects.json').read_text())
for path in paths:redirects[path+'index.html']=path
(D/'redirects.json').write_text(json.dumps(redirects,ensure_ascii=False,indent=2));(D/'_redirects').write_text('\n'.join(f'{a} {b} 301!' for a,b in redirects.items())+'\n')
# Only explicit index.html requests should redirect under Apache; DirectoryIndex is an internal request.
htaccess=(D/'.htaccess').read_text()
for path in paths:
 rule='RewriteRule ^'+re.escape((path+'index.html').lstrip('/'))+' '+path+' [R=301,L,NE]'
 if rule not in htaccess:htaccess+=rule+'\n'
htaccess=re.sub(r'(?m)^(RewriteRule .*index\\\.html[^\n]*)$',r'RewriteCond %{THE_REQUEST} \\s/+[^?\\s]*index\\.html(?:[?\\s]) [NC]\n\1',htaccess)
(D/'.htaccess').write_text(htaccess)
info.update(native_films=sum(bool(f.get('file')) for f in films),canonical_pages=len(paths),photography_collections=len(projects),portfolio_photographs=len({a['src'] for p in projects for a in p['images']}),films=len(films),city_pages=len(cities)+1)
(D/'build-info.json').write_text(json.dumps(info,ensure_ascii=False,indent=2));Path('data/language-routes.json').write_text(json.dumps(routes,ensure_ascii=False,indent=2))
print('Portfolio:',len(projects),'collections,',info['portfolio_photographs'],'unique photographs,',len(films),'films,',len(cities)+1,'city destinations;',len(paths),'bilingual pages.')
