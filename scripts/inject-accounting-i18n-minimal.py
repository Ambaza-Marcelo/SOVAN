#!/usr/bin/env python3
"""Inject minimal i18n block into accounting payment create views."""

import os
import re

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
FILES = [
    'cash_outflow/raw_material_payment_create.blade.php',
    'cash_outflow/raw_material_payment_create_before.blade.php',
    'cash_outflow/material_payment_create.blade.php',
    'cash_outflow/material_payment_create_before.blade.php',
    'cash_out_flow_request/raw_material_payment_request_create.blade.php',
    'cash_out_flow_request/raw_material_payment_request_create_before.blade.php',
    'cash_out_flow_request/material_payment_request_create.blade.php',
    'cash_out_flow_request/material_payment_request_create_before.blade.php',
]

I18N_BLOCK = """    var i18n = {
        pleaseChoose: @json(__('messages.merci de choisir')),
        delete: @json(__('messages.delete')),
        enterQuantity: @json(__('messages.Enter Quantity')),
        searchExpense: @json(__('messages.Tapez pour rechercher un article...')),
    };
"""


def process(path):
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
    if 'var i18n = {' in content or 'i18n.pleaseChoose' not in content:
        return False
    new_content = re.sub(
        r'(<script type="text/javascript">\s*\n)',
        r'\1' + I18N_BLOCK,
        content,
        count=1
    )
    if new_content == content:
        new_content = re.sub(
            r'(<script>\s*\n)',
            r'\1' + I18N_BLOCK,
            content,
            count=1
        )
    if new_content != content:
        with open(path, 'w', encoding='utf-8', newline='\n') as f:
            f.write(new_content)
        return True
    return False


def main():
    base = os.path.join(ROOT, 'resources', 'views', 'backend', 'pages', 'accounting')
    n = 0
    for rel in FILES:
        fp = os.path.join(base, rel.replace('/', os.sep))
        if os.path.isfile(fp) and process(fp):
            n += 1
            print(rel)
    print(f'Injected i18n into {n} files')


if __name__ == '__main__':
    main()
