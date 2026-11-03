"""Deterministic SEO build: canonical routes, metadata, JSON-LD and sitemaps."""
from pathlib import Path
from html import escape,unescape
from html.parser import HTMLParser
from urllib.parse import urlsplit
import os,json,re,shutil,struct,xml.etree.ElementTree as ET
D=Path('dist');config=json.loads(Path('data/site.json').read_text())
production=os.getenv('SITE_ENV')=='production'
base=os.getenv('SITE_URL',config['production_url'] if production else config['preview_url']).rstrip('/')
parsed=urlsplit(base)
if parsed.scheme not in ('http','https') or not parsed.netloc or parsed.path not in ('','/') or parsed.query or parsed.fragment:raise ValueError('SITE_URL must be an http(s) origin without a path, query or fragment')
projects=[('25 Hours Dubai','Dubai','UAE',0,'25hours-dubai'),('Fairmont Cairo','Cairo','Egypt',3,'fairmont-cairo'),('Fairmont Istanbul','Istanbul','Türkiye',4,'fairmont-istanbul'),('Fairmont Makkah','Makkah','Saudi Arabia',5,'fairmont-makkah'),('Mövenpick Resort El Quseir','El Quseir','Egypt',7,'movenpick-resort-el-quseir'),('Raffles The Palm','Dubai','UAE',8,'raffles-the-palm-dubai'),('Rixos The Palm','Dubai','UAE',13,'rixos-the-palm-dubai'),('Rixos Premium Saadiyat','Abu Dhabi','UAE',16,'rixos-premium-saadiyat-abu-dhabi')]
galleries=json.loads(Path('data/galleries.json').read_text())
def project_url(p):return '/en/projects/'+p[4]+'/'
redirects={'/':'/en/','/en/projects/':'/en/photography/','/en/portfolio/photography':'/en/photography/','/en/portfolio/film':'/en/film/','/en/about-us':'/en/about/','/en/page/about-us':'/en/about/','/en/referances':'/en/references/','/en/contact-us':'/en/contact/'}
legacy_ids=[1,4,5,6,8,9,15,18]
for p,old_id in zip(projects,legacy_ids):
 old=f'/en/projects/project-{p[3]}/';new=project_url(p);redirects[old]=new;redirects[f'/en/portfolio-detail/{old_id}']=new
 dest=D/new.strip('/');dest.mkdir(parents=True,exist_ok=True)
 (dest/'index.html').write_text((D/old.strip('/')/'index.html').read_text())
 shutil.rmtree(D/old.strip('/'))
(D/'en/index.html').write_text((D/'index.html').read_text());(D/'index.html').unlink()
(D/'en/projects/index.html').unlink(missing_ok=True)
# Preserve renamed asset aliases while giving public media meaningful names.
asset_map={}
for p in projects:
 for j,old in enumerate(galleries[str(p[3])]['images']):
  new=f'/assets/galleries/{p[4]}-{j+1:02}.jpg';target=D/new.lstrip('/')
  if not target.exists():shutil.copyfile(D/old.lstrip('/'),target)
  asset_map[old]=new
 old=f'/assets/project-{p[3]}.jpg';new=f'/assets/{p[4]}-cover.jpg'
 if not (D/new.lstrip('/')).exists():shutil.copyfile(D/old.lstrip('/'),D/new.lstrip('/'))
 asset_map[old]=new
# Small image dimension reader; avoid another runtime dependency.
def dimensions(path):
 data=path.read_bytes()
 if data[:8]==b'\x89PNG\r\n\x1a\n':return struct.unpack('>II',data[16:24])
 if data[:2]==b'\xff\xd8':
  i=2
  while i<len(data):
   if data[i]!=255:i+=1;continue
   while data[i]==255:i+=1
   marker=data[i];i+=1
   if marker in (0xd8,0xd9):continue
   length=int.from_bytes(data[i:i+2],'big')
   if marker in (0xc0,0xc1,0xc2):
    h,w=struct.unpack('>HH',data[i+3:i+7]);return w,h
   i+=length
 return None
metadata={
 '/en/':('Photography & Film | Charlot Crea','Photography and commercial film by Charlot Crea. Explore selected projects, international work and more than 25 years of photographic experience.','/assets/hero.jpg','WebPage','Home'),
 '/en/photography/':('Photography Portfolio | Charlot Crea','Explore Charlot Crea’s selected photography projects across Türkiye, the UAE, Egypt and Saudi Arabia, with full galleries and project locations.','/assets/25hours-dubai-cover.jpg','CollectionPage','Photography'),
 '/en/film/':('Commercial Film Production | Charlot Crea','Discover Charlot Crea’s approach to commercial film. Share your brand, product or service story and discuss the brief, production and delivery.','/assets/logo.png','WebPage','Film'),
 '/en/about/':('About Charlot Crea | Photography & Film','Discover Charlot Crea’s photographic approach, more than 25 years of experience, work across four continents and consultation before and after a shoot.','/assets/about-original.jpg','AboutPage','About'),
 '/en/references/':('Client References | Charlot Crea','Explore selected client references from the Charlot Crea portfolio, including Accor, Rixos, Fairmont, Swissôtel, Sofitel and Mövenpick.','/assets/logo.png','CollectionPage','References'),
 '/en/services/':('Photography & Film Services | Charlot Crea','Explore photography and commercial film services from Charlot Crea, including architecture, portraits, fashion, nature and hotel photography.','/assets/hero.jpg','WebPage','Services'),
 '/en/contact/':('Discuss a Photography or Film Project | Charlot Crea','Plan your next photography or commercial film project with Charlot Crea. Share your location, preferred timing and creative brief.','/assets/logo.png','ContactPage','Contact'),
 '/en/destinations/':('International Photography Projects | Charlot Crea','Discover Charlot Crea’s selected photography projects by destination: Türkiye, the United Arab Emirates, Egypt and Saudi Arabia.','/assets/hero.jpg','CollectionPage','Destinations'),
 '/en/destinations/uae/dubai/':('Photography in Dubai | Charlot Crea','Explore Charlot Crea’s Dubai photography portfolio, including 25 Hours Dubai, Raffles The Palm and Rixos The Palm. Discuss your next Dubai project.','/assets/raffles-the-palm-dubai-cover.jpg','CollectionPage','Dubai'),
 '/en/home-2/':('Photography & Film — Home 2 | Charlot Crea','An alternative Charlot Crea homepage concept with a full-bleed, cinematic hero treatment.','/assets/fairmont-istanbul-cover.jpg','WebPage','Home 2'),
 '/en/home-3/':('Photography & Film — Home 3 | Charlot Crea','An alternative Charlot Crea homepage concept with the showreel film playing as a full-bleed video hero.','/assets/films/charlot-crea-showreel.jpg','WebPage','Home 3')}
# Alternative homepage concepts stay unbranded in the visible breadcrumb; they are variants of the same page, not sub-pages.
home_variants=('/en/home-2/','/en/home-3/')
country_slugs={'Türkiye':'turkey','UAE':'uae','Egypt':'egypt','Saudi Arabia':'saudi-arabia'}
country_names={'Türkiye':'Türkiye','UAE':'United Arab Emirates','Egypt':'Egypt','Saudi Arabia':'Saudi Arabia'}
for country,slug in country_slugs.items():
 ps=[p for p in projects if p[2]==country];name=country_names[country]
 metadata[f'/en/destinations/{slug}/']=(f'Photography in {name} | Charlot Crea',f'Explore Charlot Crea’s selected photography in {name}, with projects in '+', '.join(dict.fromkeys(p[1] for p in ps))+'. View galleries and discuss a new project.',f'/assets/{ps[0][4]}-cover.jpg','CollectionPage',name)
for p in projects:
 name,city,country,i,slug=p
 metadata[project_url(p)]=(f'{name} Photography | Charlot Crea',f'Explore the {name} photography collection by Charlot Crea in {city}, {country_names[country]}. View {len(galleries[str(i)]["images"])} selected photographs and related projects.',f'/assets/{slug}-cover.jpg','ImageGallery',name)
orgid=base+'/#organization';webid=base+'/#website'
organization={'@type':'Organization','@id':orgid,'name':config['name'],'url':base+'/en/','logo':{'@type':'ImageObject','url':base+'/assets/logo.png'},'telephone':config['telephone'],'address':{'@type':'PostalAddress','addressLocality':config['city'],'addressCountry':config['country']}}
website={'@type':'WebSite','@id':webid,'url':base+'/en/','name':config['name'],'inLanguage':'en','publisher':{'@id':orgid}}
class Media(HTMLParser):
 def __init__(self):super().__init__();self.images=[]
 def handle_starttag(self,tag,attrs):
  a=dict(attrs)
  if tag=='img' and a.get('src') and a['src'].startswith('/assets/') and 'references/' not in a['src'] and 'logo.png' not in a['src']:self.images.append((a['src'],a.get('alt','')))
page_images={}
for path,meta in metadata.items():
 file=D/path.strip('/')/'index.html';s=file.read_text();title,description,image,page_type,label=meta
 # Rewrite only internal URL attributes, preserving query parameters and hash fragments.
 def link(m):
  prefix,url=m.groups();parts=urlsplit(unescape(url));target=redirects.get(parts.path,parts.path)
  target=asset_map.get(target,target)
  return prefix+escape(target+('?' + parts.query if parts.query else '')+('#'+parts.fragment if parts.fragment else ''),quote=True)+'"'
 s=re.sub(r'((?:href|src)=")(/[^"\s]*)"',link,s)
 if path=='/en/photography/':s=s.replace('class="page-intro"','class="page-intro"',1)
 # Visible factual project context supports both readers and machine interpretation.
 project=next((p for p in projects if project_url(p)==path),None)
 if project:
  name,city,country,i,slug=project;countryurl='/en/destinations/'+country_slugs[country]+'/'
  context=f'<section class="section project-story"><span class="label">Photography / {escape(city)}</span><div class="subgrid"><h2>{escape(name)}<br/>Through our lens.</h2><div class="bodytext"><p>A selected photography collection from {escape(name)} in {escape(city)}, {escape(country_names[country])}. Explore {len(galleries[str(i)]["images"])} photographs from the original Charlot Crea portfolio.</p><p><a class="text-link" href="{countryurl}">Explore photography in {escape(country_names[country])} ↗</a></p>'
  if city=='Dubai':context+='<p><a class="text-link" href="/en/destinations/uae/dubai/">Discover more Dubai projects ↗</a></p>'
  context+=f'<p><a class="text-link" href="/en/contact/?service=Photography&amp;country={escape(country,quote=True)}&amp;city={escape(city,quote=True)}">Discuss a photography project ↗</a></p></div></div></section>'
  s=s.replace('<section class="section full-project"',context+'<section class="section full-project"',1)
 # Country copy describes the work actually represented on the page.
 for country,slug in country_slugs.items():
  if path==f'/en/destinations/{slug}/':
   ps=[p for p in projects if p[2]==country]
   intro='Selected photography from '+', '.join(p[0] for p in ps)+f'. Explore the galleries and discuss a new project in {country_names[country]}.'
   s=re.sub(r'<p>Explore hospitality projects.*?</p>',lambda m:'<p>'+escape(intro)+'</p>',s)
 if path=='/en/destinations/uae/dubai/':
  s=s.replace('Hotel & hospitality photography in Dubai','Photography in Dubai').replace('Hotel and hospitality photography, with selected Dubai projects from the Charlot Crea portfolio.','Selected Dubai photography projects from the Charlot Crea portfolio, including 25 Hours Dubai, Raffles The Palm and Rixos The Palm.')
 if path=='/en/contact/':
  s=s.replace('<section class="form-shell">','<section class="section" style="padding-top:0;padding-bottom:30px"><p class="bodytext">Based in Antalya, Türkiye. Working internationally.</p><a class="text-link" href="tel:+905413962260">+90 541 396 22 60</a></section><section class="form-shell">')
 # Add image dimensions and responsive loading defaults without cropping.
 def img_attrs(m):
  tag=m.group(0);src=re.search(r'src="([^"]+)"',tag)
  if not src:return tag
  f=D/unescape(src.group(1)).lstrip('/')
  if not f.is_file():return tag
  dims=dimensions(f)
  if dims and 'width=' not in tag:tag=tag[:-1]+f' width="{dims[0]}" height="{dims[1]}">'
  if 'decoding=' not in tag:tag=tag[:-1]+' decoding="async">'
  return tag
 s=re.sub(r'<img\b[^>]*>',img_attrs,s)
 media=Media();media.feed(s);images=list(dict.fromkeys(media.images));page_images[path]=images
 # Visible breadcrumbs and matching structured breadcrumbs.
 crumbs=[('Home','/en/')]
 if project:crumbs += [('Photography','/en/photography/'),(label,path)]
 elif path.startswith('/en/destinations/') and path!='/en/destinations/':
  crumbs += [('Destinations','/en/destinations/')]
  if path.endswith('/dubai/'):crumbs.append(('United Arab Emirates','/en/destinations/uae/'))
  crumbs.append((label,path))
 elif path!='/en/':crumbs.append((label,path))
 breadcrumb={'@type':'BreadcrumbList','@id':base+path+'#breadcrumb','itemListElement':[{'@type':'ListItem','position':i+1,'name':n,'item':base+u} for i,(n,u) in enumerate(crumbs)]}
 if path!='/en/' and path not in home_variants:
  html='<nav class="seo-breadcrumb" aria-label="Breadcrumb">'+'<span aria-hidden="true"> / </span>'.join(f'<a href="{u}">{escape(n)}</a>' if u!=path else f'<span aria-current="page">{escape(n)}</span>' for n,u in crumbs)+'</nav>'
  s=re.sub(r'<div class="breadcrumb">.*?</div>','',s)
  s=s.replace('<main>','<main>'+html,1)
 url=base+path;image_url=base+image
 webpage={'@type':page_type,'@id':url+'#webpage','url':url,'name':title,'description':description,'inLanguage':'en','isPartOf':{'@id':webid},'publisher':{'@id':orgid},'breadcrumb':{'@id':breadcrumb['@id']},'primaryImageOfPage':{'@type':'ImageObject','url':image_url}}
 graph=[organization,website,webpage,breadcrumb]
 if project:
  photos=[{'@type':'ImageObject','@id':url+f'#photo-{i+1}','contentUrl':base+u,'name':alt,'creator':{'@id':orgid},'creditText':'Charlot Crea'} for i,(u,alt) in enumerate(images) if '/galleries/' in u]
  webpage['hasPart']=[{'@id':p['@id']} for p in photos];webpage['contentLocation']={'@type':'Place','name':project[1]+', '+country_names[project[2]]};graph+=photos
 if path in ('/en/photography/','/en/film/','/en/services/'):
  services=['Photography','Film'] if path.endswith('/services/') else ['Film' if path.endswith('/film/') else 'Photography']
  for service in services:
   graph.append({'@type':'Service','@id':url+'#'+service.lower(),'name':service+' by Charlot Crea','serviceType':service,'provider':{'@id':orgid},'url':base+('/en/film/' if service=='Film' else '/en/photography/'),'description':description})
 if page_type=='CollectionPage':
  relevant=[p for p in projects if project_url(p) in s]
  if relevant:
   listing={'@type':'ItemList','@id':url+'#projects','itemListElement':[{'@type':'ListItem','position':i+1,'name':p[0],'url':base+project_url(p)} for i,p in enumerate(relevant)]}
   webpage['mainEntity']={'@id':listing['@id']};graph.append(listing)
 robots='index,follow,max-image-preview:large' if production else 'noindex,nofollow'
 head=f'''<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{escape(title)}</title><meta name="description" content="{escape(description,quote=True)}"><meta name="robots" content="{robots}"><link rel="canonical" href="{url}"><meta name="theme-color" content="#f6f5f0"><meta property="og:type" content="website"><meta property="og:site_name" content="Charlot Crea"><meta property="og:locale" content="en_US"><meta property="og:title" content="{escape(title,quote=True)}"><meta property="og:description" content="{escape(description,quote=True)}"><meta property="og:url" content="{url}"><meta property="og:image" content="{image_url}"><meta property="og:image:alt" content="{escape(label+' — Charlot Crea',quote=True)}"><meta name="twitter:card" content="summary_large_image"><meta name="twitter:title" content="{escape(title,quote=True)}"><meta name="twitter:description" content="{escape(description,quote=True)}"><meta name="twitter:image" content="{image_url}"><meta name="twitter:image:alt" content="{escape(label+' — Charlot Crea',quote=True)}">'''
 dims=dimensions(D/image.lstrip('/'))
 if dims:head+=f'<meta property="og:image:width" content="{dims[0]}"><meta property="og:image:height" content="{dims[1]}">'
 head+='<meta property="og:image:type" content="'+('image/png' if image.endswith('.png') else 'image/jpeg')+'">'
 oldhead=re.search(r'<head>(.*?)</head>',s,re.S).group(1)
 head+=''.join(re.findall(r'<link\b[^>]*(?:rel="stylesheet"|rel="icon")[^>]*>',oldhead))
 head+='<script type="application/ld+json">'+json.dumps({'@context':'https://schema.org','@graph':graph},ensure_ascii=False).replace('<','\\u003c')+'</script>'
 s=re.sub(r'<head>.*?</head>',lambda m:'<head>'+head+'</head>',s,flags=re.S)
 s=s.replace('href="/"','href="/en/"')
 file.write_text(s)
# Normalize direct HTML and historical slash variants to one canonical URL.
redirects['/index.html']='/en/'
for path in metadata:
 redirects[path+'index.html']=path
for old,new in list(redirects.items()):
 if old!='/' and not old.endswith('.html'):
  redirects[old.rstrip('/')]=new
  redirects[old.rstrip('/')+'/']=new
# Real HTTP redirects are served by serve.py and common deployment adapters.
(D/'redirects.json').write_text(json.dumps(redirects,ensure_ascii=False,indent=2))
(D/'_redirects').write_text('\n'.join(f'{old} {new} 301!' for old,new in redirects.items())+'\n')
htaccess=['Options -Indexes','DirectoryIndex index.html','ErrorDocument 404 /404.html','RewriteEngine On']
if production:
 host_pattern=re.escape(parsed.netloc)
 htaccess.extend(['RewriteCond %{HTTPS} !=on [OR]',f'RewriteCond %{{HTTP_HOST}} !^{host_pattern}$ [NC]',f'RewriteRule ^ {base}%{{REQUEST_URI}} [R=301,L,NE]'])
for old,new in redirects.items():
 pattern='^'+re.escape(old.strip('/'))+'/?$' if old!='/' else '^$'
 htaccess.append(f'RewriteRule {pattern} {new} [R=301,L,NE]')
(D/'.htaccess').write_text('\n'.join(htaccess)+'\n')
# XML includes only canonical content URLs; actual image locations are included.
NS='http://www.sitemaps.org/schemas/sitemap/0.9';IM='http://www.google.com/schemas/sitemap-image/1.1'
ET.register_namespace('',NS);ET.register_namespace('image',IM)
root=ET.Element('{'+NS+'}urlset')
for path in metadata:
 node=ET.SubElement(root,'{'+NS+'}url');ET.SubElement(node,'{'+NS+'}loc').text=base+path
 for image,alt in page_images[path]:
  image_node=ET.SubElement(node,'{'+IM+'}image');ET.SubElement(image_node,'{'+IM+'}loc').text=base+image
ET.ElementTree(root).write(D/'sitemap.xml',encoding='utf-8',xml_declaration=True)
(D/'robots.txt').write_text('User-agent: *\n'+('Allow: /\n' if production else 'Disallow: /\n')+'\nSitemap: '+base+'/sitemap.xml\n')
(D/'404.html').write_text('<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Page not found | Charlot Crea</title><meta name="robots" content="noindex,follow"><link rel="stylesheet" href="/style.css"></head><body><main class="page-intro"><span class="label">404</span><h1>A different direction.</h1><p>This page could not be found.</p><p><a class="btn" href="/en/">Back to Charlot Crea ↗</a></p></main></body></html>')
(D/'build-info.json').write_text(json.dumps({'environment':'production' if production else 'preview','site_url':base,'canonical_pages':len(metadata)},indent=2))
print(f'SEO: {len(metadata)} canonical pages, {len(redirects)} redirects; '+('production indexing enabled' if production else 'preview noindex'))
