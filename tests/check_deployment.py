"""Smoke-test a deployed demo, including real status codes and metadata origin."""
import argparse,json,urllib.request,urllib.error,xml.etree.ElementTree as ET
class NoRedirect(urllib.request.HTTPRedirectHandler):
 def redirect_request(self,*args):return None
parser=argparse.ArgumentParser();parser.add_argument('--url',required=True);parser.add_argument('--origin',required=True);args=parser.parse_args()
url=args.url.rstrip('/');origin=args.origin.rstrip('/');client=urllib.request.build_opener(NoRedirect)
def request(path):
 try:
  with client.open(url+path,timeout=25) as r:return r.status,r.headers,r.read()
 except urllib.error.HTTPError as e:return e.code,e.headers,e.read()
for path,target in [('/','/en/'),('/en/projects/project-8/','/en/projects/raffles-the-palm-dubai/'),('/en/index.html','/en/'),('/tr/blog/index.html','/tr/blog/'),('/en/projects/project-8/?from=demo','/en/projects/raffles-the-palm-dubai/?from=demo')]:
 status,headers,_=request(path);assert status==301,(path,status);assert headers['Location']==target,(path,headers['Location'])
for path in ['/en/','/tr/','/tr/blog/','/en/blog/how-to-prepare-a-photography-brief/','/tr/iletisim/','/en/projects/raffles-the-palm-dubai/']:
 status,headers,body=request(path);assert status==200,(path,status)
 html=body.decode();assert 'href="'+origin+path+'"' in html,(path,'canonical origin')
 assert headers.get('X-Robots-Tag')=='noindex, nofollow'
status,_,_=request('/missing-page-for-deployment-check/');assert status==404,status
status,_,body=request('/sitemap.xml');assert status==200;root=ET.fromstring(body);locs=root.findall('{http://www.sitemaps.org/schemas/sitemap/0.9}url/{http://www.sitemaps.org/schemas/sitemap/0.9}loc');build_info=json.loads(request('/build-info.json')[2]);assert len(locs)==build_info['canonical_pages'];assert all(n.text.startswith(origin+'/') for n in locs)
status,_,body=request('/assets/favicon.svg');assert status==200 and b'<svg' in body
status,_,body=request('/robots.txt');assert status==200 and b'Disallow: /' in body
print('PASS: HTTP 301/404, both languages, blog, contact, gallery, sitemap, favicon and noindex on '+url)
