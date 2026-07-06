#!/usr/bin/env python3
"""Fix broken @lang inside JS strings in accounting views."""

import os
import re

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
VIEWS = os.path.join(ROOT, 'resources', 'views', 'backend', 'pages', 'accounting')

JS_PLACEHOLDER_MAP = {
    "Rechercher un compte (numéro ou nom)...": 'searchAccount',
    'Enter document no': 'enterDocumentNo',
    'Enter libelle': 'enterLibelle',
    'Enter debit': 'enterDebit',
    'Enter credit': 'enterCredit',
    'Tapez pour rechercher un article...': 'searchExpense',
    'Enter Quantity': 'enterQuantity',
}

JS_LANG_MAP = {
    'delete': 'delete',
    'addmore': 'addmore',
    'merci de choisir': 'pleaseChoose',
}


def fix_script_block(script):
    for key, i18n_var in JS_PLACEHOLDER_MAP.items():
        script = script.replace(
            f'placeholder="@lang(\'messages.{key}\')"',
            f"placeholder='\" + i18n.{i18n_var} + \"'"
        )
    for key, i18n_var in JS_LANG_MAP.items():
        script = script.replace(
            f"@lang('messages.{key}')",
            f"'\" + i18n.{i18n_var} + \"'"
        )
    script = script.replace(
        "@lang('messages.Chargement des soldes d\\'ouverture...')",
        "i18n.loadingOpeningBalances"
    )
    script = script.replace(
        "@json(__('messages.Chargement des soldes d'ouverture...'))",
        "@json(__('messages.Chargement des soldes d\\'ouverture...'))"
    )
    script = script.replace(
        "loadingOpeningBalances: @json(__('messages.Chargement des soldes d'ouverture...')),",
        "loadingOpeningBalances: @json(__('messages.Chargement des soldes d\\'ouverture...')),"
    )
    # loadingMsg line
    script = re.sub(
        r"var loadingMsg = '<tr><td colspan=\"6\" class=\"text-center\"><i class=\"fa fa-spinner fa-spin\"></i> @lang\('messages\.Chargement des soldes d\\\'ouverture\.\.\.'\)</td></tr>';",
        "var loadingMsg = '<tr><td colspan=\"6\" class=\"text-center\"><i class=\"fa fa-spinner fa-spin\"></i> ' + i18n.loadingOpeningBalances + '</td></tr>';",
        script
    )
    script = re.sub(
        r"var loadingMsg = '<tr><td colspan=\"6\" class=\"text-center\"><i class=\"fa fa-spinner fa-spin\"></i> @lang\('messages\.Chargement des soldes d\\\'ouverture\.\.\.'\)</td></tr>';",
        "var loadingMsg = '<tr><td colspan=\"6\" class=\"text-center\"><i class=\"fa fa-spinner fa-spin\"></i> ' + i18n.loadingOpeningBalances + '</td></tr>';",
        script
    )
    # Generic fallback for loading msg if still broken
    if "@lang('messages.Chargement" in script and 'loadingMsg' in script:
        script = re.sub(
            r"(\+ )?@lang\('messages\.Chargement des soldes d\\\'ouverture\.\.\.'\)",
            "i18n.loadingOpeningBalances",
            script
        )
    return script


def process_file(path):
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
    original = content

    def replacer(m):
        return m.group(1) + fix_script_block(m.group(2)) + m.group(3)

    content = re.sub(
        r'(<script[^>]*>)(.*?)(</script>)',
        replacer,
        content,
        flags=re.DOTALL
    )

    if content != original:
        with open(path, 'w', encoding='utf-8', newline='\n') as f:
            f.write(content)
        return True
    return False


def main():
    modified = []
    for dirpath, _, filenames in os.walk(VIEWS):
        for fn in filenames:
            if fn.endswith('.blade.php'):
                fp = os.path.join(dirpath, fn)
                if process_file(fp):
                    modified.append(os.path.relpath(fp, ROOT))
    print(f'Fixed JS i18n in {len(modified)} files')
    for m in sorted(modified):
        print(f'  {m}')


if __name__ == '__main__':
    main()
