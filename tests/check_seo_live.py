"""Check the running Laravel site with the sitemap as the canonical page list."""
import argparse
import json
from concurrent.futures import ThreadPoolExecutor
from html.parser import HTMLParser
from urllib.parse import urlsplit
from urllib.request import urlopen
import xml.etree.ElementTree as ET


class Page(HTMLParser):
    def __init__(self):
        super().__init__()
        self.lang = None
        self.headings = 0
        self.canonical = None
        self.alternates = {}
        self.schemas = []
        self.meta = {}
        self._json = None
        self._title = False
        self.title = ''

    def handle_starttag(self, tag, attributes):
        attrs = dict(attributes)
        if tag == 'html': self.lang = attrs.get('lang')
        if tag == 'h1': self.headings += 1
        if tag == 'title': self._title = True
        if tag == 'link' and attrs.get('rel') == 'canonical': self.canonical = attrs.get('href')
        if tag == 'link' and attrs.get('rel') == 'alternate': self.alternates[attrs.get('hreflang')] = attrs.get('href')
        if tag == 'meta': self.meta[attrs.get('name', attrs.get('property'))] = attrs.get('content')
        if tag == 'script' and attrs.get('type') == 'application/ld+json': self._json = ''

    def handle_data(self, data):
        if self._title: self.title += data
        if self._json is not None: self._json += data

    def handle_endtag(self, tag):
        if tag == 'title': self._title = False
        if tag == 'script' and self._json is not None:
            self.schemas.append(json.loads(self._json))
            self._json = None


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--base-url', default='http://localhost:8081')
    args = parser.parse_args()
    base = args.base_url.rstrip('/')
    sitemap = ET.fromstring(urlopen(base + '/sitemap.xml', timeout=30).read())
    namespace = {'s': 'http://www.sitemaps.org/schemas/sitemap/0.9'}
    urls = [element.text for element in sitemap.findall('s:url/s:loc', namespace)]
    assert urls and len(urls) == len(set(urls)), 'Empty or duplicate sitemap entries'

    def check(url):
        path = urlsplit(url).path
        response = urlopen(base + path, timeout=30)
        page = Page()
        page.feed(response.read().decode('utf-8'))
        assert response.status == 200, path
        assert page.lang == path.strip('/').split('/')[0], (path, 'language')
        assert page.headings == 1, (path, 'h1', page.headings)
        assert page.canonical == url, (path, 'canonical', page.canonical)
        assert page.title.strip() and page.meta.get('description'), (path, 'missing metadata')
        assert page.meta.get('og:url') == url, (path, 'og:url')
        assert page.meta.get('og:image:width') == '1200', (path, 'share image width')
        assert page.schemas, (path, 'missing JSON-LD')
        nodes = [node for schema in page.schemas for node in schema.get('@graph', [])]
        ids = [node['@id'] for node in nodes if '@id' in node]
        assert len(ids) == len(set(ids)), (path, 'duplicate schema identifiers')
        for node in nodes:
            if node.get('@type') == 'BreadcrumbList':
                assert node['itemListElement'][-1]['item'] == url, (path, 'breadcrumb target')
        assert 'http://localhost:8080' not in json.dumps(page.schemas), (path, 'stale schema origin')
        return url, page

    with ThreadPoolExecutor(max_workers=4) as pool:
        pages = dict(pool.map(check, urls))
    for url, page in pages.items():
        for lang, alternate in page.alternates.items():
            assert alternate in pages, (url, 'unpublished/missing alternate', alternate)
            assert pages[alternate].alternates.get(page.lang) == url, (url, 'non-reciprocal alternate', alternate)
    titles = [page.title.strip() for page in pages.values()]
    assert len(titles) == len(set(titles)), 'Duplicate page titles'
    print(f'{len(pages)} live pages passed: language, headings, metadata, canonical, hreflang, JSON-LD and sitemap.')


if __name__ == '__main__':
    main()
