"""Generated page contracts: bilingual metadata, JSON-LD, alternates and sitemaps."""
import json,re,unittest,xml.etree.ElementTree as ET
from pathlib import Path
from html.parser import HTMLParser
from urllib.parse import urlsplit,unquote
D=Path('dist')
class Page(HTMLParser):
 def __init__(self,html):
  super().__init__();self.meta={};self.links=[];self.canonical=[];self.alternates={};self.h1=0;self.ld=[];self.in_ld=False;self.lang='';self.feed(html)
 def handle_starttag(self,t,attrs):
  a=dict(attrs)
  if t=='html':self.lang=a['lang']
  if t=='meta':self.meta[a.get('name',a.get('property',''))]=a.get('content','')
  if t=='link' and a.get('rel')=='canonical':self.canonical.append(a['href'])
  if t=='link' and a.get('rel')=='alternate':self.alternates[a['hreflang']]=a['href']
  if t=='h1':self.h1+=1
  if t=='script' and a.get('type')=='application/ld+json':self.in_ld=True
  for key in ('href','src'):
   if a.get(key,'').startswith('/'):self.links.append(a[key])
 def handle_endtag(self,t):
  if t=='script':self.in_ld=False
 def handle_data(self,data):
  if self.in_ld:self.ld.append(json.loads(data))
class SEO(unittest.TestCase):
 def test_canonical_metadata_schema_sitemap(self):
  info=json.loads((D/'build-info.json').read_text());base=info['site_url'];redirects=json.loads((D/'redirects.json').read_text());pairs=json.loads(Path('data/language-routes.json').read_text());reverse={v:k for k,v in pairs.items()}
  urls=[];titles=[];descriptions=[]
  for f in D.rglob('index.html'):
   html=f.read_text();page=Page(html);path='/'+str(f.parent.relative_to(D))+'/'
   self.assertEqual(page.canonical,[base+path],f);self.assertEqual(page.h1,1,f)
   lang=path.split('/')[1];self.assertEqual(page.lang,lang)
   title=re.search('<title>(.*?)</title>',html).group(1);titles.append(title);descriptions.append(page.meta['description'])
   self.assertEqual(page.meta['og:image'],base+'/assets/charlot-crea-share.png');self.assertEqual(page.meta['twitter:image'],page.meta['og:image']);self.assertEqual(page.meta['og:image:width'],'1200');self.assertEqual(page.meta['og:image:height'],'630')
   self.assertEqual(page.meta['og:url'],base+path);self.assertEqual(page.meta['og:title'],page.meta['twitter:title'])
   self.assertEqual(page.meta['og:locale'],'tr_TR' if lang=='tr' else 'en_US')
   self.assertTrue((D/urlsplit(page.meta['og:image']).path.lstrip('/')).is_file())
   self.assertEqual('noindex' in page.meta['robots'],info['environment']=='preview')
   enpath=reverse[path] if lang=='tr' else path
   self.assertEqual(page.alternates,{'en':base+enpath,'tr':base+pairs[enpath],'x-default':base+enpath})
   self.assertEqual(len(page.ld),1)
   graph=page.ld[0]['@graph'];ids=[x['@id'] for x in graph];self.assertEqual(len(ids),len(set(ids)),f)
   wp=next(x for x in graph if x['@id']==base+path+'#webpage');self.assertEqual(wp['inLanguage'],lang)
   breadcrumb=next(x for x in graph if x['@type']=='BreadcrumbList');self.assertEqual(breadcrumb['itemListElement'][-1]['item'],base+path)
   org=next(x for x in graph if x['@type']=='Organization');self.assertEqual(org['email'],'info@charlotcrea.com');self.assertIn('2388',org['address']['streetAddress'])
   for link in page.links:
    route=unquote(urlsplit(link).path);target=D/route.lstrip('/')
    if target.is_dir():target=target/'index.html'
    self.assertTrue(target.is_file(),(f,link));self.assertNotIn(route,redirects,(f,link))
   # Blog must be reachable from the footer, but absent from the primary menu.
   nav=re.search('<nav id="nav">(.*?)</nav>',html,re.S).group(1);self.assertNotIn('/blog/',nav)
   self.assertIn('href="/'+lang+'/blog/"',re.search('<footer.*?</footer>',html,re.S).group(0))
   self.assertEqual(html.count('class="language-switch"'),1)
   self.assertNotRegex(html,r'href="/en/projects/project-\d+')
   urls.append(base+path)
  self.assertEqual(len(urls),info['canonical_pages']);self.assertEqual(len(urls),len(pairs)*2)
  self.assertEqual(len(titles),len(set(titles)));self.assertEqual(len(descriptions),len(set(descriptions)))
  tree=ET.parse(D/'sitemap.xml');ns={'s':'http://www.sitemaps.org/schemas/sitemap/0.9','i':'http://www.google.com/schemas/sitemap-image/1.1','x':'http://www.w3.org/1999/xhtml'}
  sitemap=[x.text for x in tree.findall('s:url/s:loc',ns)];self.assertEqual(set(sitemap),set(urls));self.assertEqual(len(sitemap),len(set(sitemap)))
  for node in tree.findall('s:url',ns):self.assertEqual(len(node.findall('x:link',ns)),3)
  for image in tree.findall('.//i:loc',ns):self.assertTrue((D/urlsplit(image.text).path.lstrip('/')).is_file())
  self.assertIn('Sitemap: '+base+'/sitemap.xml',(D/'robots.txt').read_text());self.assertEqual('Disallow: /' in (D/'robots.txt').read_text(),info['environment']=='preview')
  for old,new in redirects.items():self.assertNotIn(new,redirects);self.assertIn(base+new,urls)
 def test_editorial_content_and_contacts(self):
  for lang in ('en','tr'):
   files=list((D/lang/'blog').glob('*/index.html'));self.assertEqual(len(files),5)
   for file in files:
    page=Page(file.read_text());article=next(n for n in page.ld[0]['@graph'] if n['@type']=='BlogPosting')
    self.assertEqual(article['inLanguage'],lang);self.assertGreater(len(article['articleBody'].split()),140)
   path=D/('tr/iletisim/index.html' if lang=='tr' else 'en/contact/index.html');html=path.read_text()
   self.assertIn('mailto:info@charlotcrea.com',html);self.assertIn('tel:+905413962260',html);self.assertIn('output=embed',html);self.assertIn('Muratpa',html);self.assertNotIn('West%20Orange',html)
   home=(D/lang/'index.html').read_text();self.assertIn('class="editorial-ticker"',home);self.assertIn('class="logo-marquee"',home)
 def test_destination_content(self):
  from html import escape,unescape
  data=json.loads(Path('data/destinations.json').read_text());routes=json.loads(Path('data/language-routes.json').read_text())
  for entry in data:
   en='/en/destinations/'+entry['slug']+'/'
   for lang,path in [('en',en),('tr',routes[en])]:
    html=(D/path.strip('/')/'index.html').read_text();page=Page(html);copy=entry[lang]
    self.assertIn('class="section destination-story"',html)
    self.assertIn('class="destination-bg-text" aria-hidden="true"',html)
    self.assertEqual(unescape(re.search(r'<h1>(.*?)</h1>',html).group(1)),copy['h1'])
    self.assertEqual(page.meta['description'],copy['description'])
    self.assertEqual(len(re.findall('class="section destination-story"',html)),1)
    graph=page.ld[0]['@graph'];service=next(n for n in graph if n['@type']=='Service');place=next(n for n in graph if n['@type'] in ('City','Country'))
    self.assertEqual(service['areaServed']['@id'],place['@id']);self.assertEqual(place['name'],copy['name'])
    faq=next(n for n in graph if n['@type']=='FAQPage');self.assertEqual(len(faq['mainEntity']),len(copy['faq']))
    for question,answer in copy['faq']:
     self.assertIn(escape(question),html);self.assertIn(escape(answer),html)
    for heading,paragraph in copy['sections']:
     self.assertIn(escape(heading),html);self.assertIn(escape(paragraph),html)
 def test_expanded_portfolio(self):
  data=json.loads(Path('data/portfolio.json').read_text());info=json.loads((D/'build-info.json').read_text());pairs=json.loads(Path('data/language-routes.json').read_text())
  self.assertEqual(info['photography_collections'],len(data['projects']));self.assertEqual(info['films'],len(data['films']))
  unique_images={a['src'] for p in data['projects'] for a in p['images']};self.assertEqual(info['portfolio_photographs'],len(unique_images))
  for project in data['projects']:
   self.assertTrue(project['images']);en='/en/projects/'+project['slug']+'/'
   for lang,path in [('en',en),('tr',pairs[en])]:
    page=Page((D/path.strip('/')/'index.html').read_text());images=[n for n in page.ld[0]['@graph'] if n['@type']=='ImageObject'];self.assertEqual(len(images),len(project['images']))
   for asset in project['images']:
    self.assertTrue((D/asset['src'].lstrip('/')).is_file());self.assertTrue((D/asset['small'].lstrip('/')).is_file());self.assertGreater(asset['width'],0);self.assertGreater(asset['height'],0)
  for film in data['films']:
   self.assertTrue((D/film['poster'].lstrip('/')).is_file());self.assertTrue(film['embed_url'].endswith('/preview'))
   if film.get('file'):self.assertTrue((D/film['file'].lstrip('/')).is_file())
   en='/en/films/'+film['slug']+'/'
   for lang,path in [('en',en),('tr',pairs[en])]:
    html=(D/path.strip('/')/'index.html').read_text();page=Page(html);video=next(n for n in page.ld[0]['@graph'] if n['@type']=='VideoObject')
    self.assertEqual(video['thumbnailUrl'][0],info['site_url']+film['poster']);self.assertEqual(video['embedUrl'],film['embed_url']);self.assertIn('class="film-poster',html)
  for city in json.loads(Path('data/portfolio-cities.json').read_text()):
   supported=any(city['key'] in p['cities'] and p['country']==city['country'] for p in data['projects']) or any(city['key']==f['city'] and city['country']==f['country'] for f in data['films'])
   self.assertTrue(supported,city['key'])
 def test_antalya_region_includes_coastal_work(self):
  for lang,country in [('en','turkey'),('tr','turkiye')]:
   root='destinations' if lang=='en' else 'destinasyonlar'
   html=(D/lang/root/country/'antalya/index.html').read_text()
   for slug in ['balmy-beach-resort-kemer','rixos-premium-tekirova','gural-premier-belek','gural-premier-tekirova']:
    self.assertIn('/'+slug+'/',html)
   for place in ['kemer','tekirova','belek']:
    self.assertIn('/'+root+'/'+country+'/'+place+'/',html)
if __name__=='__main__':unittest.main()
