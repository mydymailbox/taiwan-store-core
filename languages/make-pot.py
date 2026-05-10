#!/usr/bin/env python3
import os, re, datetime

plugin_dir = r'c:\Users\USER\Local Sites\woo\app\public\wp-content\plugins\wc-tw-core'
domain = 'wc-tw-core'
output_pot = os.path.join(plugin_dir, 'languages', 'wc-tw-core.pot')

# Match: func( 'msgid', ['context',] 'wc-tw-core' )
I18N_RE = re.compile(
    r'(?:esc_html__|esc_attr__|esc_html_e|esc_attr_e|esc_html_x|esc_attr_x|__\b|_e\b|_x\b|_ex\b|_n\b|_nx\b)\s*'
    r'\(\s*'
    r"(?:'((?:[^'\\]|\\.)*)'|\"((?:[^\"\\]|\\.)*)\")",
    re.DOTALL
)
DOMAIN_RE = re.compile(r"['\"]wc-tw-core['\"]")
TRANS_RE  = re.compile(r'translators?:\s*(.+)', re.IGNORECASE)

entries = {}  # msgid -> {'refs': [], 'comment': str|None}

php_files = []
for root, dirs, files in os.walk(plugin_dir):
    dirs[:] = [d for d in dirs if d not in ('vendor', 'node_modules', '.git', 'docs')]
    for f in files:
        if f.endswith('.php'):
            php_files.append(os.path.join(root, f))

for fpath in sorted(php_files):
    rel = fpath.replace(plugin_dir + os.sep, '').replace(os.sep, '/')
    with open(fpath, 'r', encoding='utf-8-sig', errors='replace') as fp:
        content = fp.read()
    lines = content.splitlines()

    # Collect translators comments: line_number (1-based) -> comment
    trans_comments = {}
    for i, ln in enumerate(lines):
        m = TRANS_RE.search(ln)
        if m:
            trans_comments[i + 1] = m.group(1).strip()

    # Find all i18n calls
    for m in I18N_RE.finditer(content):
        # Check that 'wc-tw-core' appears within the same function call (next ~200 chars)
        rest = content[m.end():m.end()+300]
        if not DOMAIN_RE.search(rest):
            continue

        msgid = m.group(1) if m.group(1) is not None else (m.group(2) or '')
        # Unescape common PHP escape sequences
        msgid = msgid.replace("\\'", "'").replace('\\"', '"')
        msgid = msgid.replace('\\n', '\n').replace('\\t', '\t').replace('\\\\', '\\')
        if not msgid.strip():
            continue

        lineno = content[:m.start()].count('\n') + 1
        ref = f'{rel}:{lineno}'

        tcomment = None
        for offset in range(1, 5):
            cl = lineno - offset
            if cl in trans_comments:
                tcomment = trans_comments[cl]
                break

        if msgid not in entries:
            entries[msgid] = {'refs': [], 'comment': None}
        if ref not in entries[msgid]['refs']:
            entries[msgid]['refs'].append(ref)
        if tcomment and not entries[msgid]['comment']:
            entries[msgid]['comment'] = tcomment

print(f'Found {len(entries)} unique translatable strings')

# --- Generate .pot ---
def pot_string(s):
    s = s.replace('\\', '\\\\').replace('"', '\\"').replace('\n', '\\n"\n"').replace('\t', '\\t')
    return f'"{s}"'

now = datetime.datetime.utcnow().strftime('%Y-%m-%dT%H:%M+00:00')

lines_out = []
lines_out.append('# Copyright (C) 2026 Taiwan Store Core')
lines_out.append('# This file is distributed under the same license as the Taiwan Store Core plugin.')
lines_out.append('#')
lines_out.append('msgid ""')
lines_out.append('msgstr ""')
lines_out.append(f'"Project-Id-Version: Taiwan Store Core 1.0.1\\n"')
lines_out.append('"Report-Msgid-Bugs-To: https://github.com/your-org/wc-tw-core/issues\\n"')
lines_out.append(f'"POT-Creation-Date: {now}\\n"')
lines_out.append('"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\\n"')
lines_out.append('"Last-Translator: FULL NAME <EMAIL@ADDRESS>\\n"')
lines_out.append('"Language-Team: Chinese (Traditional) <zh-tw@li.org>\\n"')
lines_out.append('"Language: zh_TW\\n"')
lines_out.append('"MIME-Version: 1.0\\n"')
lines_out.append('"Content-Type: text/plain; charset=UTF-8\\n"')
lines_out.append('"Content-Transfer-Encoding: 8bit\\n"')
lines_out.append(f'"X-Domain: {domain}\\n"')
lines_out.append('')

for msgid, data in sorted(entries.items()):
    if data['comment']:
        lines_out.append(f'#. {data["comment"]}')
    for ref in data['refs'][:8]:  # cap refs
        lines_out.append(f'#: {ref}')
    lines_out.append('#, php-format' if '%s' in msgid or '%d' in msgid else '')
    if '#, php-format' not in lines_out[-1]:
        lines_out.pop()
    lines_out.append(f'msgid {pot_string(msgid)}')
    lines_out.append('msgstr ""')
    lines_out.append('')

os.makedirs(os.path.dirname(output_pot), exist_ok=True)
with open(output_pot, 'w', encoding='utf-8', newline='\n') as fp:
    fp.write('\n'.join(lines_out))

print(f'Written: {output_pot}')
print(f'Total entries: {len(entries)}')
