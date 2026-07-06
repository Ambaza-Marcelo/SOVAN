<?php
/**
 * Add layout/sidebar translation keys to fr, en, ki.
 */
$frPath = __DIR__ . '/../resources/lang/fr/messages.php';
$enPath = __DIR__ . '/../resources/lang/en/messages.php';
$kiPath = __DIR__ . '/../resources/lang/ki/messages.php';

$frKeys = [
    'Exchange Rate' => 'Taux de change',
    'app_admin_title' => 'EGOTEK - Administration',
    'auth_default_title' => 'Authentification - Panneau admin',
    'auth_login_title' => 'Connexion | Panneau admin',
    'stock_report_filter_export_btn' => 'Filtrer / Exporter',
    'stock_report_recent_records_hint' => 'Affichage des 100 derniers enregistrements. Utilisez le bouton ci-dessus pour filtrer ou exporter.',
    'stock_report_modal_title' => 'Filtrer ou exporter le rapport de stock',
    'stock_report_dates_required_help' => 'Les dates début et fin sont obligatoires pour afficher plus de lignes ou exporter. Le PDF affiche uniquement les quantités ; l\'Excel inclut aussi les valeurs.',
    'stock_report_store_preselect_help' => 'Stock cible présélectionné. Effacez le champ pour tous les stocks.',
    'stock_report_article_search_help' => 'Recherchez un article par code ou nom.',
    'stock_report_select_store_for_article' => 'Sélectionnez d\'abord un stock pour rechercher un article.',
    'stock_report_document_no_placeholder' => 'Entrée, sortie, réception, transfert, facture...',
    'stock_report_show_lines_btn' => 'Afficher les lignes',
    'document_preview_alt' => 'Aperçu',
    'document_preview_label' => 'Aperçu : ',
    'Choisissez le nouveau fournisseur pour' => 'Choisissez le nouveau fournisseur pour',
    'Choisir un fournisseur' => 'Choisir un fournisseur',
    'cet enregistrement' => 'cet enregistrement',
    'close' => 'Fermer',
    'ajax_autocomplete_search_error' => 'Erreur de recherche',
];

$enKeys = [
    'Exchange Rate' => 'Exchange rate',
    'header_notifications_count' => 'You have :count notification(s)',
    'header_notification_permissions_updated' => 'Permissions updated',
    'header_notification_now' => 'now',
    'goods_import_menu' => 'Goods imports',
    'app_admin_title' => 'EGOTEK - Admin',
    'auth_default_title' => 'Authentication - Admin Panel',
    'auth_login_title' => 'Login | Admin Panel',
    'stock_report_filter_export_btn' => 'Filter / Export',
    'stock_report_recent_records_hint' => 'Showing the last 100 records. Use the button above to filter or export.',
    'stock_report_modal_title' => 'Filter or export stock report',
    'stock_report_dates_required_help' => 'Start and end dates are required to show more rows or export. PDF shows quantities only; Excel also includes values.',
    'stock_report_store_preselect_help' => 'Target store pre-selected. Clear the field for all stores.',
    'stock_report_article_search_help' => 'Search for an item by code or name.',
    'stock_report_select_store_for_article' => 'Select a store first to search for an item.',
    'stock_report_document_no_placeholder' => 'Stock in, stock out, reception, transfer, invoice...',
    'stock_report_show_lines_btn' => 'Show rows',
    'document_preview_alt' => 'Preview',
    'document_preview_label' => 'Preview: ',
    'Modifier du fournisseur' => 'Edit supplier',
    'Choisissez le nouveau fournisseur pour' => 'Choose the new supplier for',
    'Choisir un fournisseur' => 'Choose a supplier',
    'cet enregistrement' => 'this record',
    'close' => 'Close',
    'Filtrer' => 'Filter',
    'Réinitialiser' => 'Reset',
    'export_excel' => 'Export Excel',
    'Date début' => 'Start date',
    'Date fin' => 'End date',
    'Aucun résultat' => 'No results',
    'ajax_autocomplete_search_error' => 'Search error',
];

$kiKeys = [
    'Exchange Rate' => 'Igipimo c\'ivyohererezwa',
    'header_notifications_count' => 'Ufise :count itangazo',
    'header_notification_permissions_updated' => 'Uburenganzira bwahindutse',
    'header_notification_now' => 'ubu',
    'goods_import_menu' => 'Kuzana ibidandazwa',
    'app_admin_title' => 'EGOTEK - Ubuyobozi',
    'auth_default_title' => 'Kwemeza - Paneli y\'ubuyobozi',
    'auth_login_title' => 'Kwinjira | Paneli y\'ubuyobozi',
    'stock_report_filter_export_btn' => 'Shungura / Sohora',
    'stock_report_recent_records_hint' => 'Kwerekana ibice 100 biheruka. Koresha buto hejuru kugira ngo ushungure canke usohore.',
    'stock_report_modal_title' => 'Shungura canke usohore raporo y\'ububiko',
    'stock_report_dates_required_help' => 'Amatariki yo gutangira n\'ayo kurangiza arakenewe kugira ngo werekane imirongo myinshi canke usohore. PDF yerekana umubare gusa; Excel irimwo n\'agaciro.',
    'stock_report_store_preselect_help' => 'Ububiko bwatoranijwe. Siba umwanya kugira ngo ubone ububiko bwose.',
    'stock_report_article_search_help' => 'Rondera ikintu ukoresheje kode canke izina.',
    'stock_report_select_store_for_article' => 'Hitamwo ububiko mbere yo kurondera ikintu.',
    'stock_report_document_no_placeholder' => 'Kwinjira, gusohoka, kwakira, gutwara, inyemezabuguzi...',
    'stock_report_show_lines_btn' => 'Erekana imirongo',
    'document_preview_alt' => 'Igaragaza',
    'document_preview_label' => 'Igaragaza: ',
    'Modifier du fournisseur' => 'Guhindura uwutanga',
    'Choisissez le nouveau fournisseur pour' => 'Hitamwo uwutanga mushasha kuri',
    'Choisir un fournisseur' => 'Hitamwo uwutanga',
    'cet enregistrement' => 'iyi nyandiko',
    'close' => 'Funga',
    'Filtrer' => 'Shungura',
    'Réinitialiser' => 'Tangura bushya',
    'export_excel' => 'Kohereza Excel',
    'Date début' => 'Itariki yo gutangira',
    'Date fin' => 'Itariki yo kurangiza',
    'Aucun résultat' => 'Nta bisubizo',
    'ajax_autocomplete_search_error' => 'Ikosa mu kurondera',
];

function mergeKeys(string $path, array $newKeys): int
{
    if (!file_exists($path) || filesize($path) < 100) {
        fwrite(STDERR, "SKIP invalid file: $path\n");
        return 0;
    }
    $content = file_get_contents($path);
    $added = 0;
    foreach ($newKeys as $k => $v) {
        $ek = str_replace("'", "\\'", $k);
        $needle = "\t'{$ek}' =>";
        if (strpos($content, $needle) !== false) {
            $ev = str_replace("'", "\\'", $v);
            $lines = explode("\n", $content);
            foreach ($lines as $i => $line) {
                if (strpos($line, $needle) === 0) {
                    $lines[$i] = "\t'{$ek}' => '{$ev}',";
                    break;
                }
            }
            $content = implode("\n", $lines);
        } else {
            $ev = str_replace("'", "\\'", $v);
            $content = preg_replace('/\];\s*\?>\s*$/s', "\t'{$ek}' => '{$ev}',\n];\n\n\n?>", $content, 1);
            $added++;
        }
    }
    file_put_contents($path, $content);
    return $added;
}

$a = mergeKeys($frPath, $frKeys);
$b = mergeKeys($enPath, $enKeys);
$c = mergeKeys($kiPath, $kiKeys);
echo "FR added: $a, EN updated/added: $b, KI updated/added: $c\n";
