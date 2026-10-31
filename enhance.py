"""Build Photography / Film sections and project galleries from local source data."""
from pathlib import Path
import json,re
from html import escape
D=Path('dist')
projects=[('25 Hours Dubai','Dubai','UAE',0),('Fairmont Cairo','Cairo','Egypt',3),('Fairmont Istanbul','Istanbul','Türkiye',4),('Fairmont Makkah','Makkah','Saudi Arabia',5),('Mövenpick Resort El Quseir','El Quseir','Egypt',7),('Raffles The Palm','Dubai','UAE',8),('Rixos The Palm','Dubai','UAE',13),('Rixos Premium Saadiyat','Abu Dhabi','UAE',16)]
template=(D/'index.html').read_text()
def page(path,title,body):
 s=re.sub(r'<main>.*?</main>',lambda m:'<main>'+body+'</main>',template,flags=re.S)
 s=re.sub(r'<title>.*?</title>',lambda m:'<title>'+title+' | Charlot Crea</title>',s)
 s=re.sub(r'<div class="brand-reveal".*?</div></div>','',s)
 p=D/path.strip('/')/'index.html';p.parent.mkdir(parents=True,exist_ok=True);p.write_text(s)
def cta(service):return f'<section class="cta"><span class="label">Your next project</span><h2>Let’s create<br/>something together.</h2><p>Share your vision, location and preferred dates.</p><a class="btn" href="/en/contact/?service={service}">Discuss a project ↗</a></section>'
work=(D/'en/projects/index.html').read_text();body=re.search(r'<main>(.*?)</main>',work,re.S).group(1)
body=body.replace('Every place,<br/>a different story.','Photography.<br/>A point of view.').replace('Selected hotel and resort photography from the Charlot Crea portfolio. Explore by destination.','Explore selected photographs from the Charlot Crea portfolio. Discover the projects and the places behind them.').replace('The portfolio','Photography / Selected projects')
body=body.replace('<section class="section" style="padding-top:10px">','<section class="section" style="padding-top:10px"><div class="discipline-note"><span>Hotels · Architecture · Portraits · Fashion · Nature</span><a class="text-link" href="/en/film/">Discover Film ↗</a></div>',1)
page('/en/photography/','Photography',body)
page('/en/film/','Film','''<section class="page-intro film-opening"><span class="label">02 / Film</span><h1>Stories.<br/>In motion.</h1><div class="film-opening-bottom"><p>Commercial films that bring the character of your brand, product or service to the screen.</p><a class="btn light" href="/en/contact/?service=Film%20production">Discuss a film ↗</a></div><div class="film-rule"><span>CHARLOT CREA</span><span>FILM / COMMERCIAL</span><span>↘</span></div></section><section class="section subgrid"><div><span class="label">The moving image</span><h2>One story.<br/>Every frame.</h2></div><div class="bodytext"><p>A film begins with what you want to communicate. We discuss your audience, your message and the intended use of the finished work.</p><p>Share your references and the story you have in mind. The creative direction, production scope and delivery requirements can then be shaped around your project.</p></div></section><section class="section film-process"><div class="section-head"><div><span class="label">From idea to screen</span><h2>A clear process.</h2></div></div><div class="approach-grid"><article><span class="label">01 / Brief</span><h3>Find the story.</h3><p>Your audience, message, references and goals define the starting point.</p></article><article><span class="label">02 / Production</span><h3>Shape the film.</h3><p>Agree the creative approach, location, schedule and production requirements.</p></article><article><span class="label">03 / Delivery</span><h3>Bring it together.</h3><p>Define the final formats, intended channels and delivery schedule in the project brief.</p></article></div></section>'''+cta('Film%20production'))
# Two distinct entrances without presenting photography as film footage.
portals='''<section class="section discipline-section"><div class="section-head"><div><span class="label">Two ways of seeing</span><h2>Photography & Film.</h2></div></div><div class="discipline-grid"><a class="discipline-card" href="/en/photography/"><span class="label">01 / Still image</span><h3>Photography</h3><p>People, places and the details that tell a story.</p><span class="discipline-link">Explore photography ↗</span></a><a class="discipline-card discipline-film" href="/en/film/"><span class="label">02 / Moving image</span><h3>Film</h3><p>Commercial films for brands, products and services.</p><span class="discipline-link">Discover film ↗</span></a></div></section>'''
p=D/'index.html';s=p.read_text();s=s.replace('<section class="section references-section">',portals+'<section class="section references-section">');p.write_text(s)
galleries=json.loads(Path('data/galleries.json').read_text())
country_slugs={'UAE':'uae','Egypt':'egypt','Türkiye':'turkey','Saudi Arabia':'saudi-arabia'}
for n,(name,city,country,i) in enumerate(projects):
 p=D/f'en/projects/project-{i}/index.html';s=p.read_text()
 images=galleries.get(str(i),{}).get('images',[])
 if images:
  gallery='<div class="photo-gallery" aria-label="'+escape(name)+' photography gallery">'+''.join(f'<a href="{u}" class="gallery-open" data-gallery="{j}" aria-label="View {escape(name)} photograph {j+1}"><img src="{u}" alt="{escape(name)} — photograph {j+1}" loading="lazy" decoding="async"></a>' for j,u in enumerate(images))+'</div>'
  s=re.sub(r'<section class="section full-project".*?</section>',lambda m:f'<section class="section full-project" style="padding-top:0"><div class="project-context"><p>{escape(name)} · {escape(city)}, {escape(country)}</p><span>{len(images):02} photographs / Selected collection</span></div>'+gallery+'</section>',s,flags=re.S)
 s=s.replace('Hospitality photography','Photography').replace('href="/en/projects/">Selected work','href="/en/photography/">Photography')
 nextp=projects[(n+1)%len(projects)]
 links=f'<section class="section project-navigation"><a class="text-link" href="/en/destinations/{country_slugs[country]}/">More work in {country} ↗</a><a class="text-link" href="/en/projects/project-{nextp[3]}/">Next project / {nextp[0]} ↗</a></section>'
 s=s.replace('<section class="cta">',links+'<section class="cta">');p.write_text(s)
navitems=[('Photography','/en/photography/'),('Film','/en/film/'),('Destinations','/en/destinations/'),('References','/en/references/'),('About','/en/about/')]
for p in D.rglob('index.html'):
 s=p.read_text();relative='/'+str(p.parent.relative_to(D))+'/'
 nav=''.join(f'<a href="{url}"'+(' class="active" aria-current="page"' if relative==url or (title=='Photography' and '/projects/' in relative) else '')+'>'+title+'</a>' for title,url in navitems)+'<a class="btn" href="/en/contact/">Discuss a project ↗</a>'
 s=re.sub(r'<nav id="nav">.*?</nav>',lambda m:'<nav id="nav">'+nav+'</nav>',s,flags=re.S)
 s=s.replace('href="/en/projects/"','href="/en/photography/"')
 s=s.replace('href="/en/services/#photography"','href="/en/photography/"').replace('href="/en/services/#film"','href="/en/film/"')
 s=s.replace('href="/en/projects/">Explore photography','href="/en/photography/">Explore photography').replace('href="/en/contact/?service=Film%20production" class="about-service"','href="/en/film/" class="about-service"')
 if '/projects/project-' in relative:s=s.replace('</main>','<dialog class="gallery-dialog" aria-label="Photo viewer"><button class="gallery-close" aria-label="Close photo viewer">×</button><button class="gallery-prev" aria-label="Previous photograph">←</button><img alt=""><button class="gallery-next" aria-label="Next photograph">→</button><p class="gallery-count" aria-live="polite"></p></dialog></main>')
 s=s.replace('</head>','<link rel="stylesheet" href="/portfolio.css"></head>').replace('</body>','<script src="/gallery.js"></script></body>')
 p.write_text(s)
print('Photography, Film and project gallery pages built.')
