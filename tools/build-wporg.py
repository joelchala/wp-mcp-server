"""Genera chala-mcp-server-wporg.zip: copia del plugin sin las herramientas SQL (wp_db_*), para WordPress.org."""
import re, shutil, zipfile, os, tempfile

src = 'wp-mcp-server'
tmp = tempfile.mkdtemp()
dst = os.path.join(tmp, 'wp-mcp-server')
shutil.copytree(src, dst)
os.remove(os.path.join(dst, 'includes/tools/class-wpmcp-tools-database.php'))

def edit(path, pairs):
    p = os.path.join(dst, path)
    s = open(p, encoding='utf-8', newline='').read()
    for pat, rep in pairs:
        s, n = re.subn(pat, rep, s, flags=re.S)
        assert n, (path, pat)
    open(p, 'w', encoding='utf-8', newline='').write(s)

edit('wp-mcp-server.php', [
    (r"require_once WPMCP_PLUGIN_DIR \. 'includes/tools/class-wpmcp-tools-database\.php';\r?\n", ''),
    (r"function wpmcp_db_tools_enabled\(\) \{.*?\}\r?\n\r?\n", ''),
    (r"    if \(wpmcp_db_tools_enabled\(\)\) \{.*?\}\r?\n", ''),
])
edit('uninstall.php', [(r"delete_option\('wpmcp_enable_db_tools'\);\r?\n", '')])
edit('admin/class-wpmcp-admin.php', [
    (r"        register_setting\('wpmcp_settings', 'wpmcp_enable_db_tools'.*?\)\);\r?\n(?=    \})", ''),
    (r"<?php echo wpmcp_db_tools_enabled\(\) \? ', base de datos' : ''; \?>", ''),
    (r"                    <tr>\s*<th scope=\"row\">Acceso SQL directo</th>.*?</tr>\r?\n", ''),
])
edit('readme.txt', [
    (r"\* \(Optional, disabled.*?\r?\n", ''),
    (r"= Database tools =.*?(?== Endpoint =)", ''),
])

# Renombrado: "wp" es término restringido en WordPress.org (nombre y slug).
for r, _, fs in os.walk(dst):
    for f in fs:
        if f.endswith(('.php', '.txt')):
            fp = os.path.join(r, f)
            t = open(fp, encoding='utf-8', newline='').read()
            t = t.replace('WP MCP Server', 'Chala MCP Server').replace('Text Domain: wp-mcp-server', 'Text Domain: chala-mcp-server')
            open(fp, 'w', encoding='utf-8', newline='').write(t)
os.rename(os.path.join(dst, 'wp-mcp-server.php'), os.path.join(dst, 'chala-mcp-server.php'))
new = os.path.join(tmp, 'chala-mcp-server')
os.rename(dst, new)
dst = new

out = os.path.abspath('chala-mcp-server-wporg.zip')
with zipfile.ZipFile(out, 'w', zipfile.ZIP_DEFLATED) as z:
    for r, _, fs in os.walk(dst):
        for f in fs:
            full = os.path.join(r, f)
            z.write(full, os.path.relpath(full, tmp).replace(os.sep, '/'))
print(out, shutil.rmtree(tmp) or 'ok')
