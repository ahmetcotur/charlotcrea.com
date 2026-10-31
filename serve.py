"""Local static server with the same permanent redirects as the deployable site."""
from functools import partial
from http.server import SimpleHTTPRequestHandler,ThreadingHTTPServer
from pathlib import Path
from urllib.parse import urlsplit,urlunsplit
import argparse,json
class Handler(SimpleHTTPRequestHandler):
 def do_HEAD(self):self.respond(head=True)
 def do_GET(self):self.respond(head=False)
 def respond(self,head=False):
  parsed=urlsplit(self.path)
  routes=json.loads((Path(self.directory)/'redirects.json').read_text())
  target=routes.get(parsed.path) or routes.get(parsed.path.rstrip('/')+'/') or routes.get(parsed.path.rstrip('/'))
  if target:
   self.send_response(301);self.send_header('Location',urlunsplit(('', '',target,parsed.query,'')));self.send_header('Content-Length','0');self.end_headers();return
  if head:super().do_HEAD()
  else:super().do_GET()
 def send_error(self,code,message=None,explain=None):
  error_page=Path(self.directory)/'404.html'
  if code==404 and error_page.exists():
   data=error_page.read_bytes();self.send_response(404);self.send_header('Content-Type','text/html; charset=utf-8');self.send_header('Content-Length',str(len(data)));self.end_headers()
   if self.command!='HEAD':self.wfile.write(data)
  else:super().send_error(code,message,explain)
if __name__=='__main__':
 parser=argparse.ArgumentParser();parser.add_argument('--port',type=int,default=8080);parser.add_argument('--bind',default='127.0.0.1');parser.add_argument('--directory',default='dist');args=parser.parse_args()
 server=ThreadingHTTPServer((args.bind,args.port),partial(Handler,directory=args.directory))
 print(f'Charlot Crea: http://{args.bind}:{args.port}',flush=True)
 try:server.serve_forever()
 except KeyboardInterrupt:server.server_close()
