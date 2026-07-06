#!/usr/bin/env python3
"""Repair i18n: restore @lang in HTML, fix JS button/option concatenation."""

import os
import re

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
VIEWS = os.path.join(ROOT, 'resources', 'views', 'backend', 'pages', 'accounting')

HTML_REVERT = [
    ("'\" + i18n.delete + \"'", "@lang('messages.delete')"),
    ("'\" + i18n.addmore + \"'", "@lang('messages.addmore')"),
    ("'\" + i18n.pleaseChoose + \"'", "@lang('messages.merci de choisir')"),
]

JS_FIXES = [
    ("remove-tr'>'\" + i18n.delete + \"'</button>", "remove-tr'>\" + i18n.delete + \"</button>"),
    ("btn-success'>'\" + i18n.addmore + \"'</button>", "btn-success'>\" + i18n.addmore + \"</button>"),
    ("for='vat_rate'>'\" + i18n.pleaseChoose + \"'<strong", "for='vat_rate'>\" + i18n.pleaseChoose + \"<strong"),
    ("<option selected disabled>'\" + i18n.pleaseChoose + \"'</option>", "<option selected disabled>\" + i18n.pleaseChoose + \"</option>"),
    ("<option disabled=\"disabled\" selected=\"selected\">'\" + i18n.pleaseChoose + \"'</option>", "<option disabled=\"disabled\" selected=\"selected\">@lang('messages.merci de choisir')</option>"),
    ("<option>'\" + i18n.pleaseChoose + \"'</option>", "<option>\" + i18n.pleaseChoose + \"</option>"),
]


def split_scripts(content):
    parts = []
    last = 0
    for m in re.finditer(r'<script[^>]*>.*?</script>', content, re.DOTALL):
        if m.start() > last:
            parts.append(('html', content[last:m.start()]))
        parts.append(('script', m.group(0)))
        last = m.end()
    if last < len(content):
        parts.append(('html', content[last:]))
    return parts if parts else [('html', content)]


def process_file(path):
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
    original = content
    out = []
    for kind, chunk in split_scripts(content):
        if kind == 'html':
            for old, new in HTML_REVERT:
                chunk = chunk.replace(old, new)
        else:
            for old, new in JS_FIXES:
                chunk = chunk.replace(old, new)
        out.append(chunk)
    content = ''.join(out)
    if content != original:
        with open(path, 'w', encoding='utf-8', newline='\n') as f:
            f.write(content)
        return True
    return False


def main():
    n = 0
    for dirpath, _, filenames in os.walk(VIEWS):
        for fn in filenames:
            if fn.endswith('.blade.php'):
                if process_file(os.path.join(dirpath, fn)):
                    n += 1
    print(f'Repaired {n} files')


if __name__ == '__main__':
    main()
