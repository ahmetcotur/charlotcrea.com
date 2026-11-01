"""Use consistent vector controls instead of platform-dependent Unicode glyphs."""
from pathlib import Path
from html.parser import HTMLParser
paths={'↗':'M5 19 19 5M5 5h14v14','↘':'M5 5l14 14M5 19h14V5','→':'M4 12h16m-6-6 6 6-6 6','←':'M20 12H4m6-6-6 6 6 6','↓':'M12 4v16m-6-6 6 6 6-6','↑':'M12 20V4m-6 6 6-6 6 6','×':'m6 6 12 12M6 18 18 6','☰':'M4 6h16M4 12h16M4 18h16','▶':'m8 5 11 7-11 7Z','✳':'M12 3v18M3 12h18M6 6l12 12M6 18 18 6'}
def icon(char):
 return '<svg class="ui-icon" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="'+paths[char]+'" fill="'+('currentColor' if char=='▶' else 'none')+'" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>'
class Icons(HTMLParser):
 def __init__(self):super().__init__(convert_charrefs=False);self.output=[];self.skip=[]
 def handle_starttag(self,tag,attrs):
  self.output.append(self.get_starttag_text())
  if tag in ('script','style','svg','textarea','title'):self.skip.append(tag)
 def handle_startendtag(self,tag,attrs):self.output.append(self.get_starttag_text())
 def handle_endtag(self,tag):
  self.output.append('</'+tag+'>')
  if self.skip and self.skip[-1]==tag:self.skip.pop()
 def handle_data(self,data):self.output.append(data if self.skip else ''.join(icon(c) if c in paths else c for c in data))
 def handle_entityref(self,name):self.output.append('&'+name+';')
 def handle_charref(self,name):self.output.append('&#'+name+';')
 def handle_decl(self,decl):self.output.append('<!'+decl+'>')
 def handle_comment(self,data):self.output.append('<!--'+data+'-->')
for file in Path('dist').rglob('index.html'):
 parser=Icons();parser.feed(file.read_text());s=''.join(parser.output)
 s=s.replace('</head>','<meta name="format-detection" content="telephone=no,date=no,address=no,email=no,url=no"><link rel="stylesheet" href="/icons.css"></head>')
 label='Başa dön' if '<html lang="tr"' in s else 'Back to top'
 button='<button type="button" class="back-to-top" aria-label="'+label+'" hidden>'+icon('↑')+'</button>'
 s=s.replace('</body>',button+'<script src="/icons.js"></script><script src="/back-to-top.js"></script></body>');file.write_text(s)
# Keep controls updated by JS (brief forms and video modal links) consistent too.
import json
Path('dist/icons.js').write_text("(()=>{const icons="+json.dumps({c:icon(c) for c in paths},ensure_ascii=False)+";function replace(node){if(node.nodeType===3){if(!/[↗↘→←↓↑×☰▶✳]/.test(node.data)||node.parentElement?.closest('script,style,svg,textarea,title'))return;const f=document.createDocumentFragment();for(const c of node.data){if(icons[c]){const t=document.createElement('template');t.innerHTML=icons[c];f.append(t.content)}else f.append(document.createTextNode(c))}node.replaceWith(f)}else if(node.nodeType===1&&!node.matches('script,style,svg,textarea,title'))[...node.childNodes].forEach(replace)}const observer=new MutationObserver(records=>records.forEach(r=>{if(r.type==='characterData')replace(r.target);else r.addedNodes.forEach(replace)}));observer.observe(document.body,{childList:true,subtree:true,characterData:true})})();")
# Safari must fetch new animation and icon assets after each deployment.
import hashlib
for file in Path('dist').rglob('index.html'):
 s=file.read_text()
 for asset in ('motion.js','icons.js','icons.css','back-to-top.js'):
  version=hashlib.sha256((Path('dist')/asset).read_bytes()).hexdigest()[:12]
  s=s.replace('/'+asset+'"','/'+asset+'?v='+version+'"')
 file.write_text(s)
