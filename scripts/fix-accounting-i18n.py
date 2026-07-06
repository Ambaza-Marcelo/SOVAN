#!/usr/bin/env python3
"""Fix i18n in resources/views/backend/pages/accounting/**"""

import os
import re

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
VIEWS = os.path.join(ROOT, 'resources', 'views', 'backend', 'pages', 'accounting')
LANG_DIR = os.path.join(ROOT, 'resources', 'lang')

# HTML placeholder: literal -> messages key
PLACEHOLDER_MAP = {
    'Tapez pour rechercher un compte...': 'Tapez pour rechercher un compte...',
    'Tapez pour rechercher un employé...': 'hr_type_to_search_employee',
    'Tapez pour rechercher un article...': 'Tapez pour rechercher un article...',
    'Enter Description': 'Enter Description',
    'Enter Quantity': 'Enter Quantity',
    'Enter quantity': 'Enter Quantity',
    'Type to search customer...': 'Tapez pour rechercher un client...',
    'Type to search account...': 'Tapez pour rechercher un compte...',
    'Type to search account (418)...': 'Type to search account (418)...',
    'Enter contact name': 'Enter contact name',
    'Enter contact phone number': 'Enter contact phone number',
    'Enter amount': 'Enter amount',
    'Enter description': 'Enter Description',
    'Rechercher un compte': 'Rechercher un compte',
    'Rechercher par numéro ou nom de compte...': 'Rechercher un compte (numéro ou nom)...',
    'Enter document no': 'Enter document no',
    'Enter libelle': 'Enter libelle',
    'Enter debit': 'Enter debit',
    'Enter credit': 'Enter credit',
    'Saisir le demandeur': 'Entrer le demandeur',
    'Montant sorti': 'Montant sorti',
    'Entrer le montant retourné': 'Entrer le montant retourné',
    'Entrer la description': 'Entrer description',
    'Enter Full name of Asker': 'Enter Full name of Asker',
    'Rechercher le fournisseur': 'type_to_search_supplier',
    'Enter expense name': 'Enter expense name',
    'Enter expense cost': 'Enter expense cost',
    'e.g. 2024': 'fiscal_year_placeholder',
    'Ex. : FN0001-20260122': 'ledger_piece_document_filter_placeholder',
}

CONFIRM_MAP = {
    "Are you sure you want to delete this item? This action is irreversible.": 'delete_confirm_irreversible',
    "Êtes-vous sûr de vouloir supprimer ce compte parent ? Cette action est irréversible.": 'accounting_delete_parent_account_confirm',
    "Êtes-vous sûr de vouloir supprimer ce compte enfant ? Cette action est irréversible.": 'accounting_delete_child_account_confirm',
    "Êtes-vous sûr de vouloir supprimer ce retour de fonds?": 'accounting_delete_return_fund_confirm',
    "Êtes-vous sûr de vouloir supprimer cette dépense ? Cette action est irréversible.": 'accounting_delete_expense_confirm',
    "Êtes-vous sûr de vouloir supprimer ce paramètre?": 'accounting_delete_setting_confirm',
    "Are you sure you want to validate this advance payment?": 'accounting_validate_advance_payment_confirm',
    "Are you sure you want to confirm this advance payment?": 'accounting_confirm_advance_payment_confirm',
    "Are you sure you want to approve this advance payment? This will create journal entries.": 'accounting_approve_advance_payment_confirm',
    "Are you sure you want to validate this fund transfer?": 'accounting_validate_fund_transfer_confirm',
    "Are you sure you want to confirm this fund transfer?": 'accounting_confirm_fund_transfer_confirm',
    "Are you sure you want to approve this fund transfer? This will create journal entries.": 'accounting_approve_fund_transfer_confirm',
    "Êtes-vous sûr de vouloir valider ce retour de fonds?": 'accounting_validate_return_fund_confirm',
    "Êtes-vous sûr de vouloir confirmer ce retour de fonds?": 'accounting_confirm_return_fund_confirm',
    "Êtes-vous sûr de vouloir approuver ce retour de fonds? Cela créera les écritures comptables.": 'accounting_approve_return_fund_confirm',
    "Changer le type en OPENING va remplacer toutes les lignes existantes. Voulez-vous continuer?": 'journal_entry_opening_type_confirm',
    "Voulez-vous créer un retour de fonds pour cette sortie de fonds ?\\n\\nCliquez sur OK pour créer un retour de fonds.\\nCliquez sur Annuler pour marquer cette sortie comme sans retour de fonds.": 'cash_outflow_return_fund_prompt',
}

# Keys to add/update in lang files
NEW_KEYS = {
    'en': {
        'Type to search account (418)...': 'Type to search account (418)...',
        'Enter contact name': 'Enter contact name',
        'Enter contact phone number': 'Enter contact phone number',
        'Enter amount': 'Enter amount',
        'Rechercher un compte': 'Search for an account',
        'Enter document no': 'Enter document number',
        'Enter libelle': 'Enter label',
        'Enter debit': 'Enter debit',
        'Enter credit': 'Enter credit',
        'Montant sorti': 'Amount withdrawn',
        'Entrer le montant retourné': 'Enter returned amount',
        'Enter Full name of Asker': 'Enter full name of requester',
        'Enter expense name': 'Enter expense name',
        'Enter expense cost': 'Enter expense cost',
        'fiscal_year_placeholder': 'e.g. 2024',
        'ledger_piece_document_filter_placeholder': 'Ex.: FN0001-20260122',
        'accounting_delete_parent_account_confirm': 'Are you sure you want to delete this parent account? This action cannot be undone.',
        'accounting_delete_child_account_confirm': 'Are you sure you want to delete this child account? This action cannot be undone.',
        'accounting_delete_return_fund_confirm': 'Are you sure you want to delete this fund return?',
        'accounting_delete_expense_confirm': 'Are you sure you want to delete this expense? This action cannot be undone.',
        'accounting_delete_setting_confirm': 'Are you sure you want to delete this setting?',
        'accounting_validate_advance_payment_confirm': 'Are you sure you want to validate this advance payment?',
        'accounting_confirm_advance_payment_confirm': 'Are you sure you want to confirm this advance payment?',
        'accounting_approve_advance_payment_confirm': 'Are you sure you want to approve this advance payment? This will create journal entries.',
        'accounting_validate_fund_transfer_confirm': 'Are you sure you want to validate this fund transfer?',
        'accounting_confirm_fund_transfer_confirm': 'Are you sure you want to confirm this fund transfer?',
        'accounting_approve_fund_transfer_confirm': 'Are you sure you want to approve this fund transfer? This will create journal entries.',
        'accounting_validate_return_fund_confirm': 'Are you sure you want to validate this fund return?',
        'accounting_confirm_return_fund_confirm': 'Are you sure you want to confirm this fund return?',
        'accounting_approve_return_fund_confirm': 'Are you sure you want to approve this fund return? This will create journal entries.',
        'journal_entry_opening_type_confirm': 'Changing the type to OPENING will replace all existing lines. Do you want to continue?',
        'cash_outflow_return_fund_prompt': 'Do you want to create a fund return for this cash outflow?\n\nClick OK to create a fund return.\nClick Cancel to mark this outflow as having no fund return.',
        'journal_entry_opening_no_manual_rows': 'You cannot manually add rows for OPENING type. Use the "Load opening balances" button.',
        'Chargement des soldes d\'ouverture...': 'Loading opening balances...',
        'Create Child Account': 'Create child account',
        'Enter account name': 'Enter account name',
        'Enter account number': 'Enter account number',
        'Journal Entry': 'Journal entry',
        'new journal entry': 'New journal entry',
        'edit fund transfer': 'Edit fund transfer',
        'fund transfer details': 'Fund transfer details',
        'new fund transfer': 'New fund transfer',
        'Tapez pour rechercher un employé...': 'Type to search for an employee...',
    },
    'fr': {
        'Type to search account (418)...': 'Tapez pour rechercher un compte (418)...',
        'Enter contact name': 'Entrer le nom du contact',
        'Enter contact phone number': 'Entrer le téléphone du contact',
        'Enter amount': 'Entrer le montant',
        'Rechercher un compte': 'Rechercher un compte',
        'Enter document no': 'Entrer le numéro de document',
        'Enter libelle': 'Entrer le libellé',
        'Enter debit': 'Entrer le débit',
        'Enter credit': 'Entrer le crédit',
        'Montant sorti': 'Montant sorti',
        'Entrer le montant retourné': 'Entrer le montant retourné',
        'Enter Full name of Asker': 'Entrer le nom complet du demandeur',
        'Enter expense name': 'Entrer le nom de la dépense',
        'Enter expense cost': 'Entrer le coût de la dépense',
        'fiscal_year_placeholder': 'ex. 2024',
        'ledger_piece_document_filter_placeholder': 'Ex. : FN0001-20260122',
        'accounting_delete_parent_account_confirm': 'Êtes-vous sûr de vouloir supprimer ce compte parent ? Cette action est irréversible.',
        'accounting_delete_child_account_confirm': 'Êtes-vous sûr de vouloir supprimer ce compte enfant ? Cette action est irréversible.',
        'accounting_delete_return_fund_confirm': 'Êtes-vous sûr de vouloir supprimer ce retour de fonds?',
        'accounting_delete_expense_confirm': 'Êtes-vous sûr de vouloir supprimer cette dépense ? Cette action est irréversible.',
        'accounting_delete_setting_confirm': 'Êtes-vous sûr de vouloir supprimer ce paramètre?',
        'accounting_validate_advance_payment_confirm': 'Êtes-vous sûr de vouloir valider ce paiement d\'avance?',
        'accounting_confirm_advance_payment_confirm': 'Êtes-vous sûr de vouloir confirmer ce paiement d\'avance?',
        'accounting_approve_advance_payment_confirm': 'Êtes-vous sûr de vouloir approuver ce paiement d\'avance? Cela créera les écritures comptables.',
        'accounting_validate_fund_transfer_confirm': 'Êtes-vous sûr de vouloir valider ce transfert de fonds?',
        'accounting_confirm_fund_transfer_confirm': 'Êtes-vous sûr de vouloir confirmer ce transfert de fonds?',
        'accounting_approve_fund_transfer_confirm': 'Êtes-vous sûr de vouloir approuver ce transfert de fonds? Cela créera les écritures comptables.',
        'accounting_validate_return_fund_confirm': 'Êtes-vous sûr de vouloir valider ce retour de fonds?',
        'accounting_confirm_return_fund_confirm': 'Êtes-vous sûr de vouloir confirmer ce retour de fonds?',
        'accounting_approve_return_fund_confirm': 'Êtes-vous sûr de vouloir approuver ce retour de fonds? Cela créera les écritures comptables.',
        'journal_entry_opening_type_confirm': 'Changer le type en OPENING va remplacer toutes les lignes existantes. Voulez-vous continuer?',
        'cash_outflow_return_fund_prompt': 'Voulez-vous créer un retour de fonds pour cette sortie de fonds ?\n\nCliquez sur OK pour créer un retour de fonds.\nCliquez sur Annuler pour marquer cette sortie comme sans retour de fonds.',
        'journal_entry_opening_no_manual_rows': 'Vous ne pouvez pas ajouter manuellement des lignes pour le type OPENING. Utilisez le bouton "Charger les soldes d\'ouverture".',
        'Chargement des soldes d\'ouverture...': 'Chargement des soldes d\'ouverture...',
    },
    'ki': {
        'Type to search account (418)...': 'Andika rondera konti (418)...',
        'Enter contact name': 'Injiza izina ry\'umuntu w\'itumanaho',
        'Enter contact phone number': 'Injiza telefoni y\'umuntu w\'itumanaho',
        'Enter amount': 'Injiza amafaranga',
        'Rechercher un compte': 'Rondera konti',
        'Enter document no': 'Injiza numero y\'inyandiko',
        'Enter libelle': 'Injiza ibisobanuro',
        'Enter debit': 'Injiza ideni',
        'Enter credit': 'Injiza inguzanyo',
        'Montant sorti': 'Amafaranga yavuye',
        'Entrer le montant retourné': 'Injiza amafaranga yasubijwe',
        'Enter Full name of Asker': 'Injiza izina ryuzuye ry\'usaba',
        'Enter expense name': 'Injiza izina ry\'igiciro',
        'Enter expense cost': 'Injiza igiciro cy\'igiciro',
        'fiscal_year_placeholder': 'urug. 2024',
        'ledger_piece_document_filter_placeholder': 'Urug.: FN0001-20260122',
        'accounting_delete_parent_account_confirm': 'Urashaka koko gusiba iyi konti nyina? Ntibishobora kugarurwa.',
        'accounting_delete_child_account_confirm': 'Urashaka koko gusiba iyi konti y\'umwana? Ntibishobora kugarurwa.',
        'accounting_delete_return_fund_confirm': 'Urashaka koko gusiba iyi gusubiza amafaranga?',
        'accounting_delete_expense_confirm': 'Urashaka koko gusiba iki giciro? Ntibishobora kugarurwa.',
        'accounting_delete_setting_confirm': 'Urashaka koko gusiba iyi setting?',
        'accounting_validate_advance_payment_confirm': 'Urashaka koko kwemeza iyi wishyura y\'imbere?',
        'accounting_confirm_advance_payment_confirm': 'Urashaka koko kwemeza iyi wishyura y\'imbere?',
        'accounting_approve_advance_payment_confirm': 'Urashaka koko kwemeza iyi wishyura y\'imbere? Ibi bizarema inyandiko z\'imari.',
        'accounting_validate_fund_transfer_confirm': 'Urashaka koko kwemeza iyi transfer?',
        'accounting_confirm_fund_transfer_confirm': 'Urashaka koko kwemeza iyi transfer?',
        'accounting_approve_fund_transfer_confirm': 'Urashaka koko kwemeza iyi transfer? Ibi bizarema inyandiko z\'imari.',
        'accounting_validate_return_fund_confirm': 'Urashaka koko kwemeza iyi gusubiza amafaranga?',
        'accounting_confirm_return_fund_confirm': 'Urashaka koko kwemeza iyi gusubiza amafaranga?',
        'accounting_approve_return_fund_confirm': 'Urashaka koko kwemeza iyi gusubiza amafaranga? Ibi bizarema inyandiko z\'imari.',
        'journal_entry_opening_type_confirm': 'Guhindura ubwoko bwa OPENING bizasimbuza imirongo yose. Urashaka gukomeza?',
        'cash_outflow_return_fund_prompt': 'Urashaka gukora gusubiza amafaranga kuri iyi so de fonds?\n\nKanda OK gukora gusubiza.\nKanda Cancel gushyira iyi so nta gusubiza.',
        'journal_entry_opening_no_manual_rows': 'Ntushobora kongeramo imirongo mu buryo bwa OPENING. Koresha buto "Charger les soldes d\'ouverture".',
        'Chargement des soldes d\'ouverture...': 'Gupakira soldes d\'ouverture...',
        'Tapez pour rechercher un employé...': 'Andika rondera umukozi...',
    },
}

# JS i18n keys used in dynamic rows
JS_I18N_KEYS = {
    'searchAccount': 'Rechercher un compte (numéro ou nom)...',
    'searchExpense': 'Tapez pour rechercher un article...',
    'enterQuantity': 'Enter Quantity',
    'enterDescription': 'Enter Description',
    'enterDocumentNo': 'Enter document no',
    'enterLibelle': 'Enter libelle',
    'enterDebit': 'Enter debit',
    'enterCredit': 'Enter credit',
    'delete': 'delete',
    'addmore': 'addmore',
    'pleaseChoose': 'merci de choisir',
    'loadingOpeningBalances': 'Chargement des soldes d\'ouverture...',
    'openingNoManualRows': 'journal_entry_opening_no_manual_rows',
}

FILES_NEED_JS_I18N = {
    'journal_entry/create.blade.php',
    'journal_entry/edit.blade.php',
    'cash_outflow/create.blade.php',
    'cash_outflow/edit.blade.php',
    'cash_out_flow_request/create.blade.php',
    'cash_out_flow_request/edit.blade.php',
}


def escape_php(s):
    return s.replace('\\', '\\\\').replace("'", "\\'")


def fix_html_placeholders(content):
    for literal, key in PLACEHOLDER_MAP.items():
        # placeholder="literal"
        content = content.replace(
            f'placeholder="{literal}"',
            f'placeholder="@lang(\'messages.{key}\')"'
        )
        content = content.replace(
            f"placeholder='{literal}'",
            f"placeholder=\"@lang('messages.{key}')\""
        )
    return content


def fix_js_placeholders(content):
    """Replace hardcoded placeholders inside JS strings with i18n references."""
    js_map = {
        'Tapez pour rechercher un article...': 'searchExpense',
        'Enter Quantity': 'enterQuantity',
        'Enter quantity': 'enterQuantity',
        'Rechercher par numéro ou nom de compte...': 'searchAccount',
        'Enter document no': 'enterDocumentNo',
        'Enter libelle': 'enterLibelle',
        'Enter debit': 'enterDebit',
        'Enter credit': 'enterCredit',
    }
    for literal, i18n_key in js_map.items():
        content = content.replace(
            f"placeholder='{literal}'",
            f"placeholder='\" + i18n.{i18n_key} + \"'"
        )
    return content


def fix_confirms(content):
    for literal, key in CONFIRM_MAP.items():
        for quote in ("'", '"'):
            old = f"confirm({quote}{literal}{quote})"
            new = f"confirm(@json(__('messages.{key}')))"
            content = content.replace(old, new)
    return content


def fix_lang_in_js_strings(content):
    """Replace @lang inside JS string literals with i18n object references."""
    content = re.sub(
        r"@lang\('messages\.delete'\)",
        "'\" + i18n.delete + \"'",
        content
    )
    content = re.sub(
        r"@lang\('messages\.addmore'\)",
        "'\" + i18n.addmore + \"'",
        content
    )
    content = re.sub(
        r"@lang\('messages\.merci de choisir'\)",
        "'\" + i18n.pleaseChoose + \"'",
        content
    )
    content = re.sub(
        r"@lang\('messages\.Chargement des soldes d\\\'ouverture\.\.\.'\)",
        "'\" + i18n.loadingOpeningBalances + \"'",
        content
    )
    return content


def inject_i18n_block(content, rel_path):
    if 'var i18n = {' in content:
        return content
    if rel_path.replace('\\', '/') not in FILES_NEED_JS_I18N:
        # Still fix @lang in JS for other files with script tags
        if '<script' in content and "@lang('messages." in content:
            pass
        else:
            return content

    lines = []
    for var, key in JS_I18N_KEYS.items():
        lines.append(f"        {var}: @json(__('messages.{key}')),")
    i18n_block = "    var i18n = {\n" + "\n".join(lines).rstrip(',') + "\n    };\n"

    # Insert after first <script type="text/javascript"> or <script>
    patterns = [
        r'(<script type="text/javascript">\s*\n)',
        r'(<script>\s*\n)',
        r'(<script type="text/javascript">)',
    ]
    for pat in patterns:
        m = re.search(pat, content)
        if m:
            insert_at = m.end()
            return content[:insert_at] + i18n_block + content[insert_at:]
    return content


def fix_journal_alerts(content):
    old = "alert('Vous ne pouvez pas ajouter manuellement des lignes pour le type OPENING. Utilisez le bouton \"Charger les soldes d\\'ouverture\".');"
    new = "alert(i18n.openingNoManualRows);"
    content = content.replace(old, new)
    # variant without escaped quote
    old2 = "alert('Vous ne pouvez pas ajouter manuellement des lignes pour le type OPENING. Utilisez le bouton \"Charger les soldes d'ouverture\".');"
    content = content.replace(old2, new)
    return content


def update_lang_file(lang, updates):
    path = os.path.join(LANG_DIR, lang, 'messages.php')
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()

    for key, value in updates.items():
        ek = escape_php(key)
        ev = escape_php(value)
        pattern = rf"\t'{re.escape(ek)}'\s*=>\s*'(?:[^'\\]|\\.)*',"
        replacement = f"\t'{ek}' => '{ev}',"
        if re.search(pattern, content):
            content = re.sub(pattern, replacement, content)
        elif f"'{ek}' =>" not in content:
            content = content.replace('];\n\n\n?>', f"\t'{ek}' => '{ev}',\n];\n\n\n?>")

    with open(path, 'w', encoding='utf-8', newline='\n') as f:
        f.write(content)


def process_file(path):
    rel = os.path.relpath(path, VIEWS)
    with open(path, 'r', encoding='utf-8') as f:
        original = f.read()
    content = original
    content = fix_html_placeholders(content)
    content = fix_confirms(content)
    rel_norm = rel.replace('\\', '/')
    if rel_norm in FILES_NEED_JS_I18N or ('<script' in content and 'placeholder=' in content and 'var markup' in content):
        content = inject_i18n_block(content, rel)
        content = fix_js_placeholders(content)
        content = fix_lang_in_js_strings(content)
        content = fix_journal_alerts(content)
    elif '@lang(' in content and 'var markup' in content:
        content = inject_i18n_block(content, rel)
        content = fix_js_placeholders(content)
        content = fix_lang_in_js_strings(content)

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

    for lang in ('en', 'fr', 'ki'):
        update_lang_file(lang, NEW_KEYS[lang])

    print(f'Modified {len(modified)} files:')
    for m in sorted(modified):
        print(f'  {m}')


if __name__ == '__main__':
    main()
