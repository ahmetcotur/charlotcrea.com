"""Readable bilingual destination copy with project evidence and restrained place typography."""
from pathlib import Path
from html import escape
from urllib.parse import urlencode
import json,re
D=Path('dist');base=json.loads((D/'build-info.json').read_text())['site_url']
entries=json.loads(Path('data/destinations.json').read_text());routes=json.loads(Path('data/language-routes.json').read_text())
for entry in entries:
 enpath='/en/destinations/'+entry['slug']+'/'
 for lang in ('en','tr'):
  d=entry[lang];path=routes[enpath] if lang=='tr' else enpath;file=D/path.strip('/')/'index.html';s=file.read_text();is_tr=lang=='tr'
  labels={'context':'Mekân ve bakış' if is_tr else 'Place & perspective','work':'İlgili projeleri keşfedin' if is_tr else 'Explore related projects','faq':'Çekimi planlarken' if is_tr else 'Planning the shoot','sources':'Bölge hakkında kaynaklar' if is_tr else 'Destination references','brief':'Brief hazırlama rehberi ↗' if is_tr else 'Photography brief guide ↗','contact':'Bu destinasyonda çekim planlayın ↗' if is_tr else 'Plan a shoot in this destination ↗'}
  opening='<section class="destination-opening"><span class="destination-bg-text" aria-hidden="true">'+escape(d['background'])+'</span><div class="destination-opening-content"><span class="label">'+('Fotoğraf / Destinasyonlar' if is_tr else 'Photography / Destinations')+'</span><h1>'+escape(d['h1'])+'</h1><p>'+escape(d['lead'])+'</p></div></section>'
  s=s.replace('<main>','<main class="destination-page">',1)
  s=re.sub(r'<section class="page-intro">.*?</section>',lambda m:opening,s,count=1,flags=re.S)
  if entry['slug']=='uae/dubai':
   # Replace the generic planning notes with destination-specific visible copy and questions.
   s=re.sub(r'<section class="section subgrid">.*?</section>','',s,count=1,flags=re.S)
   s=re.sub(r'<section class="section"><span class="label">(?:Before we begin|Başlamadan önce)</span>.*?</section>','',s,count=1,flags=re.S)
  story='<section class="section destination-story"><div class="destination-story-intro"><div><span class="label">'+labels['context']+'</span><h2>'+escape(d['heading'])+'</h2></div><p>'+escape(d['intro'])+'</p></div><div class="destination-topic-grid">'
  for i,(heading,text) in enumerate(d['sections']):story+='<article><span class="label">'+str(i+1).zfill(2)+'</span><h3>'+escape(heading)+'</h3><p>'+escape(text)+'</p></article>'
  story+='</div><div class="destination-related"><span class="label">'+labels['work']+'</span><div>'
  for slug in entry['projects']:
   enproject='/en/projects/'+slug+'/';projectpath=routes[enproject] if is_tr else enproject
   title=re.search('<h1>(.*?)</h1>',(D/projectpath.strip('/')/'index.html').read_text(),re.S).group(1)
   story+='<a class="text-link" href="'+projectpath+'">'+title+' ↗</a>'
  if entry['slug']=='uae':story+='<a class="text-link" href="'+(routes['/en/destinations/uae/dubai/'] if is_tr else '/en/destinations/uae/dubai/')+'">'+('Dubai fotoğraf çalışmaları ↗' if is_tr else 'Dubai photography ↗')+'</a>'
  story+='</div></div><div class="destination-faq"><h2>'+labels['faq']+'</h2>'
  for q,a in d['faq']:story+='<details class="faq"><summary>'+escape(q)+'</summary><p>'+escape(a)+'</p></details>'
  contact='/tr/iletisim/' if is_tr else '/en/contact/'
  query=urlencode({'service':'Photography','country':{'United Arab Emirates':'UAE'}.get(entry['country'],entry['country'])})
  if len(entry['cities'])==1:query+='&'+urlencode({'city':entry['cities'][0]})
  brief='/tr/blog/fotograf-cekimi-briefi-nasil-hazirlanir/' if is_tr else '/en/blog/how-to-prepare-a-photography-brief/'
  story+='</div><div class="destination-actions"><a class="btn" href="'+escape(contact+'?'+query,quote=True)+'">'+labels['contact']+'</a><a class="text-link" href="'+brief+'">'+labels['brief']+'</a></div><details class="destination-sources"><summary>'+labels['sources']+'</summary><ul>'
  story+=''.join('<li><a href="'+escape(source['url'],quote=True)+'" target="_blank" rel="noopener noreferrer">'+escape(source['title'])+' ↗</a></li>' for source in entry['sources'])+'</ul></details></section>'
  s=s.replace('<section class="cta">',story+'<section class="cta">',1)
  # The visible subject and site metadata describe the same destination and project collection.
  s=re.sub(r'<title>.*?</title>',lambda m:'<title>'+escape(d['meta'])+'</title>',s,count=1)
  def meta(m):
   tag=m.group(0);field=re.search(r'(?:name|property)="([^"]+)"',tag)
   if not field:return tag
   value=d['meta'] if field[1] in ('og:title','twitter:title') else d['description'] if field[1] in ('description','og:description','twitter:description') else None
   if value is None:return tag
   return re.sub(r'content="[^"]*"',lambda _: 'content="'+escape(value,quote=True)+'"',tag)
  s=re.sub(r'<meta\b[^>]*>',meta,s)
  schema_match=re.search(r'<script type="application/ld\+json">(.*?)</script>',s,re.S);schema=json.loads(schema_match[1]);graph=schema['@graph']
  webpage=next(n for n in graph if n['@id']==base+path+'#webpage');webpage.update({'name':d['meta'],'description':d['description']})
  place_id=base+path+'#place';place_node={'@type':'City' if entry['slug']=='uae/dubai' else 'Country','@id':place_id,'name':d['name']}
  if entry['slug']=='uae/dubai':place_node['containedInPlace']={'@type':'Country','name':'Birleşik Arap Emirlikleri' if is_tr else 'United Arab Emirates'}
  webpage['about']={'@id':place_id};webpage['spatialCoverage']={'@id':place_id};webpage['keywords']=', '.join(d['keywords']);webpage['citation']=[source['url'] for source in entry['sources']];graph.append(place_node)
  service={'@type':'Service','@id':base+path+'#photography-service','name':d['h1'],'serviceType':'Fotoğraf' if is_tr else 'Photography','description':d['description'],'provider':{'@id':base+'/#organization'},'areaServed':{'@id':place_id},'url':base+path};graph.append(service)
  faqid=base+path+'#questions';graph.append({'@type':'FAQPage','@id':faqid,'inLanguage':lang,'mainEntity':[{'@type':'Question','name':q,'acceptedAnswer':{'@type':'Answer','text':a}} for q,a in d['faq']]});webpage['hasPart']=[{'@id':faqid}]
  schema_json=json.dumps(schema,ensure_ascii=False).replace('<','\\u003c')
  s=re.sub(r'<script type="application/ld\+json">.*?</script>',lambda m:'<script type="application/ld+json">'+schema_json+'</script>',s,count=1,flags=re.S)
  file.write_text(s)
print('Destination copy: 5 destinations, EN + TR, matching metadata and place/service schema.')

# Record the editorial update date for these existing canonical URLs only.
import xml.etree.ElementTree as ET
NS='http://www.sitemaps.org/schemas/sitemap/0.9';ET.register_namespace('',NS);ET.register_namespace('image','http://www.google.com/schemas/sitemap-image/1.1');ET.register_namespace('xhtml','http://www.w3.org/1999/xhtml')
updates={}
for entry in entries:
    enpath='/en/destinations/'+entry['slug']+'/'
    updates[base+enpath]=entry['updated']
    updates[base+routes[enpath]]=entry['updated']
tree=ET.parse(D/'sitemap.xml')
for node in tree.getroot():
    loc=node.find('{'+NS+'}loc')
    if loc is not None and loc.text in updates:
        lastmod=node.find('{'+NS+'}lastmod')
        if lastmod is None:lastmod=ET.SubElement(node,'{'+NS+'}lastmod')
        lastmod.text=updates[loc.text]
tree.write(D/'sitemap.xml',encoding='utf-8',xml_declaration=True)
