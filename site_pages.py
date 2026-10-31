"""Shared helpers for additional bilingual static portfolio pages."""
from pathlib import Path
from html import escape
import json,re,copy
D=Path('dist')
def graph(html):return json.loads(re.search(r'<script type="application/ld\+json">(.*?)</script>',html,re.S).group(1))['@graph']
def rewrite_head(html,base,path,alternate,title,description,graph_nodes):
 lang='tr' if path.startswith('/tr/') else 'en';other='en' if lang=='tr' else 'tr';url=base+path
 head=re.search(r'<head>(.*?)</head>',html,re.S).group(1)
 head=re.sub(r'<title>.*?</title>',lambda m:'<title>'+escape(title)+'</title>',head)
 def meta(m):
  tag=m[0];field=re.search(r'(?:name|property)="([^"]+)"',tag)
  if not field:return tag
  key=field[1];value=title if key in ('og:title','twitter:title') else description if key in ('description','og:description','twitter:description') else url if key=='og:url' else 'tr_TR' if key=='og:locale' and lang=='tr' else 'en_US' if key=='og:locale' else 'en_US' if key=='og:locale:alternate' and lang=='tr' else 'tr_TR' if key=='og:locale:alternate' else None
  if value is None:return tag
  return re.sub(r'content="[^"]*"',lambda _: 'content="'+escape(value,quote=True)+'"',tag)
 head=re.sub(r'<meta\b[^>]*>',meta,head)
 head=re.sub(r'<link rel="canonical"[^>]*>',lambda m:'<link rel="canonical" href="'+url+'">',head)
 head=re.sub(r'<link rel="alternate"[^>]*>','',head)
 en=path if lang=='en' else alternate;tr=path if lang=='tr' else alternate
 head+=''.join(f'<link rel="alternate" hreflang="{code}" href="{base+target}">' for code,target in [('en',en),('tr',tr),('x-default',en)])
 head=re.sub(r'<script type="application/ld\+json">.*?</script>','',head,flags=re.S)
 head+='<script type="application/ld+json">'+json.dumps({'@context':'https://schema.org','@graph':graph_nodes},ensure_ascii=False).replace('<','\\u003c')+'</script>'
 if '/work.css' not in head:head+='<link rel="stylesheet" href="/work.css">'
 html=re.sub(r'<head>.*?</head>',lambda m:'<head>'+head+'</head>',html,flags=re.S)
 html=re.sub(r'<html lang="[^"]+"',f'<html lang="{lang}"',html)
 # Replace the entire switcher so the current page always links to its own counterpart.
 switch='<div class="language-switch" role="group" aria-label="'+('Dil seçimi' if lang=='tr' else 'Choose language')+'">'+''.join(f'<a href="{target}" lang="{code}" hreflang="{code}"'+(' aria-current="page"' if code==lang else '')+'>'+code.upper()+'</a>' for code,target in [('tr',tr),('en',en)])+'</div>'
 html=re.sub(r'<div class="language-switch".*?</div>',lambda m:switch,html,count=1,flags=re.S)
 if '/work.js' not in html:html=html.replace('</body>','<script src="/work.js"></script></body>')
 return html

def schema(base,path,title,description,kind,crumbs,template,image='/assets/hero.jpg'):
 seed=graph(template);org=copy.deepcopy(next(n for n in seed if n['@type']=='Organization'));web=copy.deepcopy(next(n for n in seed if n['@type']=='WebSite'));lang=path.split('/')[1]
 breadcrumb={'@type':'BreadcrumbList','@id':base+path+'#breadcrumb','itemListElement':[{'@type':'ListItem','position':i+1,'name':n,'item':base+u} for i,(n,u) in enumerate(crumbs)]}
 wp={'@type':kind,'@id':base+path+'#webpage','url':base+path,'name':title,'description':description,'inLanguage':lang,'isPartOf':{'@id':web['@id']},'publisher':{'@id':org['@id']},'breadcrumb':{'@id':breadcrumb['@id']},'primaryImageOfPage':{'@type':'ImageObject','url':base+image}}
 return [org,web,wp,breadcrumb]

def breadcrumb(crumbs,lang):return '<nav class="seo-breadcrumb" aria-label="'+('Sayfa yolu' if lang=='tr' else 'Breadcrumb')+'">'+'<span aria-hidden="true"> / </span>'.join(f'<a href="{url}">{escape(name)}</a>' if i<len(crumbs)-1 else '<span aria-current="page">'+escape(name)+'</span>' for i,(name,url) in enumerate(crumbs))+'</nav>'
