#!/usr/bin/env python3
"""Compile a .po file into a .mo file (GNU gettext binary format)."""
import struct, sys, os

def unescape_po(s):
    return s.replace('\\n', '\n').replace('\\t', '\t').replace('\\"', '"').replace('\\\\', '\\')

def parse_po(path):
    entries = []
    msgid = msgstr = None
    in_msgstr = False

    with open(path, 'r', encoding='utf-8') as f:
        for raw_line in f:
            line = raw_line.rstrip('\n')

            if line.startswith('msgid '):
                if msgid is not None and msgstr is not None:
                    entries.append((msgid, msgstr))
                msgid = unescape_po(line[7:-1])
                msgstr = None
                in_msgstr = False

            elif line.startswith('msgstr '):
                msgstr = unescape_po(line[8:-1])
                in_msgstr = True

            elif line.startswith('"'):
                chunk = unescape_po(line[1:-1])
                if in_msgstr and msgstr is not None:
                    msgstr += chunk
                elif not in_msgstr and msgid is not None:
                    msgid += chunk

    if msgid is not None and msgstr is not None:
        entries.append((msgid, msgstr))

    # Keep only entries with non-empty translations
    return [(k, v) for k, v in entries if v.strip()]

def compile_mo(po_path, mo_path):
    entries = parse_po(po_path)
    entries.sort(key=lambda x: x[0].encode('utf-8'))
    N = len(entries)

    # Offsets: header=28, orig table=28..28+N*8, trans table after that
    HEADER = 28
    ORIG_OFF  = HEADER
    TRANS_OFF = ORIG_OFF  + N * 8
    STR_OFF   = TRANS_OFF + N * 8

    orig_data  = b''
    trans_data = b''
    orig_table  = []
    trans_table = []

    cur = 0
    for k, v in entries:
        kb = k.encode('utf-8') + b'\x00'
        orig_table.append((len(kb) - 1, STR_OFF + cur))
        orig_data += kb
        cur += len(kb)

    cur2 = cur
    for k, v in entries:
        vb = v.encode('utf-8') + b'\x00'
        trans_table.append((len(vb) - 1, STR_OFF + cur2))
        trans_data += vb
        cur2 += len(vb)

    mo = bytearray()
    # Magic, revision, num_strings, orig_offset, trans_offset, hash_size, hash_offset
    mo += struct.pack('<IIIIIII', 0x950412de, 0, N, ORIG_OFF, TRANS_OFF, 0, STR_OFF)
    for length, offset in orig_table:
        mo += struct.pack('<II', length, offset)
    for length, offset in trans_table:
        mo += struct.pack('<II', length, offset)
    mo += orig_data + trans_data

    with open(mo_path, 'wb') as f:
        f.write(mo)
    print(f'Compiled {N} entries -> {mo_path}')

if __name__ == '__main__':
    base = r'c:\Users\USER\Local Sites\woo\app\public\wp-content\plugins\wc-tw-core\languages'
    po = os.path.join(base, 'wc-tw-core-en.po')
    mo = os.path.join(base, 'wc-tw-core-en.mo')
    compile_mo(po, mo)
