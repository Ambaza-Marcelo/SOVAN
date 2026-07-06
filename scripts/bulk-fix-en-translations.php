<?php
/**
 * Fix EN values that still contain French accents for keys used in backend/pages views.
 * Applies a curated map; extend as needed.
 */

$enPath = __DIR__ . '/../resources/lang/en/messages.php';
$en = include $enPath;

$viewsDir = __DIR__ . '/../resources/views/backend/pages';
$keys = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsDir));
foreach ($it as $file) {
    if (!$file->isFile() || substr($file->getFilename(), -10) !== '.blade.php') continue;
    $c = file_get_contents($file->getPathname());
    preg_match_all("/@lang\('messages\.([^']+)'\)/", $c, $m);
    foreach ($m[1] as $k) $keys[$k] = true;
    preg_match_all("/__\('messages\.([^']+)'\)/", $c, $m2);
    foreach ($m2[1] as $k) $keys[$k] = true;
}

$bulk = [
    'Bons de commande' => 'Sales orders',
    'factures' => 'Invoices',
    'foods' => 'Raw materials',
    'reception' => 'Reception',
    'reception partielle' => 'Partial reception',
    'transfer' => 'Transfer',
    'return' => 'Return',
    'stockin' => 'Stock in',
    'stockout' => 'Stock out',
    'inventory' => 'Inventory',
    'list' => 'List',
    'new' => 'New',
    'edit' => 'Edit',
    'delete' => 'Delete',
    'update' => 'Update',
    'dashboard' => 'Dashboard',
    'admin_panel' => 'Admin Panel',
    'Liste' => 'List',
    'Liste des clients' => 'Client list',
    'Informations' => 'Information',
    'Informations Générales' => 'General information',
    'Détails' => 'Details',
    'Détail' => 'Detail',
    'Date' => 'Date',
    'Description' => 'Description',
    'Quantité' => 'Quantity',
    'Article' => 'Item',
    'Matière première' => 'Raw material',
    'Matières premières' => 'Raw materials',
    'Aucune matière première' => 'No raw material',
    'Validé' => 'Validated',
    'Rejeté' => 'Rejected',
    'Confirmé' => 'Confirmed',
    'Approuvé' => 'Approved',
    'En cours' => 'In progress',
    'Valider' => 'Validate',
    'Confirmer' => 'Confirm',
    'Approuver' => 'Approve',
    'Modifier' => 'Edit',
    'Imprimer' => 'Print',
    'Retour' => 'Back',
    'Auteur' => 'Author',
    'État' => 'Status',
    'Signature' => 'Signature',
    'Coût total' => 'Total cost',
    'N° Plan' => 'Plan No.',
    'Infos' => 'Info',
    'Créé par' => 'Created by',
    'Validé par' => 'Validated by',
    'Confirmé par' => 'Confirmed by',
    'Approuvé par' => 'Approved by',
    'Début / Fin' => 'Start / End',
    'Prix unit.' => 'Unit price',
    'Employés' => 'Employees',
    'Fiche des paies' => 'Payroll records',
    'Les Congés pris' => 'Leaves taken',
    'Prendre un congé payé' => 'Take paid leave',
    'Enregistrer un Employé' => 'Register employee',
    'Modifier un Employé' => 'Edit employee',
    'Ecole' => 'School',
    'Liste de paies pour un mois dernier' => 'Pay list for last month',
    'Liste des remboursements cautions' => 'Deposit refund list',
    'Nouveau remboursement caution' => 'New deposit refund',
    'Demande Achat des Articles' => 'Purchase request',
    'Demande de Requisition des Articles' => 'Requisition request',
    'details des receptions' => 'Reception details',
    'Modifier la reception' => 'Edit reception',
    'Nouvelle reception totale' => 'New full reception',
    'MOUVEMENT DE GRAND STOCK' => 'BIG STORE MOVEMENT',
    'RAPPORT DU STOCK DES MATIERES PREMIERES' => 'RAW MATERIALS STOCK REPORT',
    'RAPPORT DU STOCK DE MATIÈRES PREMIÈRES (GRAND STOCK)' => 'RAW MATERIALS STOCK REPORT (BIG STORE)',
    'RAPPORT DU STOCK DE MATERIELS (PETIT STOCK)' => 'MATERIALS STOCK REPORT (SMALL STORE)',
    'RAPPORT DU STOCK DES PRODUITS FINIS' => 'FINISHED PRODUCTS STOCK REPORT',
    'Plan de production' => 'Production plan',
    'Créer requête matières premières' => 'Create raw materials requisition',
    'Livré' => 'Delivered',
    'Voir document' => 'View document',
    'Télécharger' => 'Download',
    'Ajouter' => 'Add',
    'Editer' => 'Edit',
    'Supprimer' => 'Delete',
    'Oui' => 'Yes',
    'Non' => 'No',
    'merci de choisir' => 'Please choose',
    'Thank you to choose' => 'Please choose',
    'Balance must be?' => 'Balance must be?',
    'Debit' => 'Debit',
    'Credit' => 'Credit',
    'Clients' => 'Clients',
    'Provisions' => 'Provisions',
    'Accounts' => 'Accounts',
    'Currency' => 'Currency',
    'Status' => 'Status',
    'General' => 'General',
    'Taxes Accounts' => 'Tax accounts',
    'Enter account name' => 'Enter account name',
    'Enter account number' => 'Enter account number',
    'Enter description' => 'Enter description',
    'Edit account' => 'Edit account',
    'Create account setting' => 'Create account setting',
    'Journal Entry' => 'Journal entry',
    'accounts_balance_title' => 'Accounts balance',
    'article_create' => 'Create item',
    'goods_store_create_title' => 'Create goods store',
    'goods_store_edit_title' => 'Edit goods store',
    'material_big_store_create_title' => 'Create material big store',
    'material_big_store_edit_title' => 'Edit material big store',
    'material_big_store_detail_prefix' => 'Material big store detail ',
    'create new finished goods store' => 'Create new finished goods store',
    'Edit finished goods store' => 'Edit finished goods store',
    'create new material big store' => 'Create new material big store',
    'Edit material big store' => 'Edit material big store',
    'create new material small store' => 'Create new material small store',
    'Edit material small store' => 'Edit material small store',
    'stockin_edit' => 'Edit stock in',
    'stockout_edit' => 'Edit stock out',
    'setting_edit' => 'Edit setting',
    'sortie de fonds' => 'Cash outflow',
    'reception totale' => 'Full reception',
    'nouvelle entreprise' => 'New company',
    'production order details' => 'Production order details',
    'quantity_requisitioned' => 'Quantity requisitioned',
    'quantity_returned' => 'Quantity returned',
    'quantity_transfered' => 'Quantity transferred',
    'new_purchase_price' => 'New purchase price',
    'new_selling_price' => 'New selling price',
    'new_total_purchase_price' => 'New total purchase price',
    'new_total_selling_price' => 'New total selling price',
];

$applied = 0;
foreach ($bulk as $k => $v) {
    if (isset($en[$k])) {
        $en[$k] = $v;
        $applied++;
    }
}

$content = file_get_contents($enPath);
foreach ($bulk as $k => $v) {
    $ek = str_replace("'", "\\'", $k);
    $ev = str_replace("'", "\\'", $v);
    $pattern = "/\t'{$ek}'\s*=>\s*'[^']*(?:\\\\'[^']*)*',/";
    if (preg_match($pattern, $content)) {
        $content = preg_replace($pattern, "\t'{$ek}' => '{$ev}',", $content);
    }
}

// Append keys not in file
$toAdd = '';
foreach ($bulk as $k => $v) {
    $ek = str_replace("'", "\\'", $k);
    if (strpos($content, "'{$ek}' =>") === false) {
        $ev = str_replace("'", "\\'", $v);
        $toAdd .= "\t'{$ek}' => '{$ev}',\n";
    }
}
if ($toAdd) {
    $content = str_replace("];\n\n\n?>", $toAdd . "];\n\n\n?>", $content);
}

file_put_contents($enPath, $content);

$frenchEnRemaining = 0;
foreach (array_keys($keys) as $k) {
    if (!isset($en[$k])) continue;
    $v = $en[$k];
    if (preg_match('/[àâäéèêëïîôùûüçœæ]/iu', $v)) {
        $frenchEnRemaining++;
    }
}

echo "Bulk map applied: " . count($bulk) . "\n";
echo "French-accent EN values remaining (used keys): $frenchEnRemaining\n";
