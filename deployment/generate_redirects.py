"""Write Nginx's map from the same routes used by local and other hosts."""
from pathlib import Path
import json
routes=json.loads(Path('dist/redirects.json').read_text())
Path('deployment/redirects.conf').write_text(''.join(json.dumps(old)+' '+json.dumps(new)+';\n' for old,new in sorted(routes.items())))
