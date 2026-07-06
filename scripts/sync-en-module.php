<?php
/**
 * Sync EN translations for keys used in given module folders.
 * Usage: php sync-en-module.php sales_order proforma_invoice invoice ...
 */

$modules = array_slice($argv, 1);
if (empty($modules)) {
    fwrite(STDERR, "Usage: php sync-en-module.php <module> [module2...]\n");
    exit(1);
}

$enPath = __DIR__ . '/../resources/lang/en/messages.php';
$en = include $enPath;

$keys = [];
foreach ($modules as $module) {
    $dir = __DIR__ . '/../resources/views/backend/pages/' . $module;
    if (!is_dir($dir)) {
        continue;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($it as $file) {
        if (!$file->isFile() || substr($file->getFilename(), -10) !== '.blade.php') {
            continue;
        }
        $c = file_get_contents($file->getPathname());
        preg_match_all("/@lang\('messages\.([^']+)'\)/", $c, $m);
        foreach ($m[1] as $k) {
            $keys[$k] = true;
        }
        preg_match_all("/__\('messages\.([^']+)'\)/", $c, $m2);
        foreach ($m2[1] as $k) {
            $keys[$k] = true;
        }
    }
}

// English translations for French keys (module Ventes + shared)
$map = [
    'item' => 'Item',
    'quantity' => 'Quantity',
    'Total' => 'Total',
    'Action' => 'Action',
    'Total Général' => 'Grand Total',
    '0 BIF' => '0 BIF',
    'Document (PDF / Image)' => 'Document (PDF / Image)',
    'PDF, JPG, JPEG, PNG' => 'PDF, JPG, JPEG, PNG',
    'Edit proforma invoice' => 'Edit proforma invoice',
    'Bons de commande' => 'Sales orders',
    'N° Bon de commande' => 'Sales order No.',
    'Edit Facture' => 'Edit invoice',
    'Détail' => 'Detail',
    'Les données sont synchronisées depuis la facture proforma:' => 'Data synced from proforma invoice:',
    'Facture Proforma:' => 'Proforma invoice:',
    'Aucun détail pour ce bon de commande.' => 'No details for this sales order.',
    'TOTAL GÉNÉRAL:' => 'GRAND TOTAL:',
    'Aucun détail de facture proforma trouvé!' => 'No proforma invoice details found!',
    'Create Delivery Note' => 'Create delivery note',
    'Create Invoice' => 'Create invoice',
    'FA' => 'FA',
    'Type de Monaie' => 'Currency type',
    'Creation du client' => 'Create client',
    'Creation du Client' => 'Create client',
    'Type de contribuable' => 'Taxpayer type',
    'merci de choisir' => 'Please choose',
    'Personne Physique' => 'Individual',
    'société locale' => 'Local company',
    'société étrangère' => 'Foreign company',
    'NIF du Client' => 'Client TIN',
    'Nom du Client' => 'Client name',
    'Telephone du Client' => 'Client phone',
    'Adresse du Client' => 'Client address',
    'Modifier du client' => 'Edit client',
    'Modifier du Client' => 'Edit client',
    'Modifier du fournisseur' => 'Edit supplier',
    'Modifier du Fournisseur' => 'Edit supplier',
    'Creation du fournisseur' => 'Create supplier',
    'Liste des fournisseurs' => 'Supplier list',
    'Liste des clients' => 'Client list',
    'Prix Unitaire' => 'Unit price',
    'date' => 'Date',
    'description' => 'Description',
    'addmore' => 'Add more',
    'save' => 'Save',
    'cancel' => 'Cancel',
    'Client' => 'Client',
    'Supprimer' => 'Delete',
    'Validation de facture à cash' => 'Cash invoice validation',
    'Aucun résultat trouvé' => 'No results found',
    'Vous pouvez modifier cette facture car son état est 0.' => 'You can edit this invoice because its status is 0.',
    'Numéro Facture' => 'Invoice number',
    'Numéro Facture:' => 'Invoice number:',
    'Les données sont synchronisées depuis la facture proforma:' => 'Data synced from proforma invoice:',
    'Aucun détail de bon de livraison trouvé!' => 'No delivery note details found!',
    'Facture Proforma Associée' => 'Associated proforma invoice',
    'Réduction hors facture' => 'Discount off invoice',
    'Enregistrer la note de crédit' => 'Save credit note',
    'Réf. facture' => 'Invoice ref.',
    'même compte que celui débité sur la facture' => 'Same account debited on the invoice',
    'Aucun client lié.' => 'No linked client.',
    'Bon de livraison associé' => 'Associated delivery note',
    'Facture source (état)' => 'Source invoice (status)',
    'Choisir un client' => 'Choose a client',
    'Merci de choisir Client' => 'Please choose a client',
    'Rechercher un article...' => 'Search for an item...',
    'Rechercher un article (PF, marchandise)...' => 'Search for an item (FG, goods)...',
    'Entrer quantite' => 'Enter quantity',
    'Enter Description' => 'Enter description',
    'Enter Quantity' => 'Enter quantity',
    'Tapez pour rechercher un client...' => 'Type to search for a client...',
    'Tapez pour rechercher un compte...' => 'Type to search for an account...',
    'Rechercher un item...' => 'Search for an item...',
    'Modifier BC' => 'Edit SO',
    'Modifier BL' => 'Edit DN',
    'Créer Bon de commande' => 'Create sales order',
    'Créer Bon de commande (données de la facture proforma auto-complétées)' => 'Create sales order (proforma data auto-filled)',
    'Modifier le Bon de commande' => 'Edit sales order',
    'Modifier le Bon de Livraison' => 'Edit delivery note',
    'Déjà livré' => 'Already delivered',
    'Facture Proforma No:' => 'Proforma invoice No:',
    'Facture No:' => 'Invoice No:',
    'Bon de Livraison No:' => 'Delivery note No:',
    'Créer Facture - Bon de Livraison:' => 'Create invoice - Delivery note:',
    'Créer Facture - Bon de commande:' => 'Create invoice - Sales order:',
    'Créer Bon de Livraison - Facture Proforma:' => 'Create delivery note - Proforma invoice:',
    'Créer Bon de livraison - Facture:' => 'Create delivery note - Invoice:',
    'Bon de livraison - Facture' => 'Delivery note - Invoice',
    'Créer Facture - BC' => 'Create invoice - SO',
    'Voir facture' => 'View invoice',
    'Modifier' => 'Edit',
    'Valider' => 'Validate',
    'Confirmer' => 'Confirm',
    'Approuver' => 'Approve',
    'Imprimer (PDF)' => 'Print (PDF)',
    'Imprimer (PDF BIF)' => 'Print (PDF BIF)',
    'Imprimer (PDF USD)' => 'Print (PDF USD)',
    'Imprimer en USD (PDF)' => 'Print in USD (PDF)',
    'Imprimer (BIF)' => 'Print (BIF)',
    'PDF USD' => 'PDF USD',
    'Supprimer ce bon de commande ?' => 'Delete this sales order?',
    'Êtes-vous sûr de vouloir supprimer ce bon de livraison ?' => 'Are you sure you want to delete this delivery note?',
    'Entrer le NIF du Client' => 'Enter client TIN',
    'Entrer le nom du Client' => 'Enter client name',
    'Entrer le telephone' => 'Enter phone number',
    'Entrer l\'adresse' => 'Enter address',
    'Entrer le mail' => 'Enter email',
    'Rechercher un compte (numéro ou nom)...' => 'Search for an account (number or name)...',
    'Bon de Livraison:' => 'Delivery note:',
    'Facture Proforma:' => 'Proforma invoice:',
];

$updated = 0;
foreach (array_keys($keys) as $k) {
    if (isset($map[$k])) {
        $en[$k] = $map[$k];
        $updated++;
    }
}
// Always apply full map (for new keys used in blades we'll add)
foreach ($map as $k => $v) {
    $en[$k] = $v;
    if (!isset($keys[$k])) {
        $updated++;
    }
}

$content = file_get_contents($enPath);
$insert = '';
foreach ($map as $k => $v) {
    $escapedKey = str_replace("'", "\\'", $k);
    $escapedVal = str_replace("'", "\\'", $v);
    $line = "\t'{$escapedKey}' => '{$escapedVal}',\n";
    if (strpos($content, "'{$escapedKey}' =>") === false) {
        $insert .= $line;
    }
}

// Rewrite entire en file with updated values for existing keys
$lines = file($enPath);
$out = [];
$inArray = false;
foreach ($lines as $line) {
    if (preg_match("/^\t'([^']*(?:\\\\'[^']*)*)'\s*=>\s*'([^']*(?:\\\\'[^']*)*)',?\s*$/", $line, $m)) {
        $key = str_replace("\\'", "'", $m[1]);
        if (isset($en[$key]) && isset($map[$key])) {
            $ek = str_replace("'", "\\'", $key);
            $ev = str_replace("'", "\\'", $map[$key]);
            $out[] = "\t'{$ek}' => '{$ev}',\n";
            continue;
        }
        if (isset($map[$key])) {
            $ek = str_replace("'", "\\'", $key);
            $ev = str_replace("'", "\\'", $map[$key]);
            $out[] = "\t'{$ek}' => '{$ev}',\n";
            continue;
        }
    }
    $out[] = $line;
    if (trim($line) === '];' && $insert !== '') {
        // Insert new keys before closing
        array_pop($out);
        $out[] = $insert;
        $out[] = "];\n";
        $insert = '';
    }
}

if ($insert !== '') {
    $final = implode('', $out);
    $final = str_replace("];\n\n\n?>", $insert . "];\n\n\n?>", $final);
    file_put_contents($enPath, $final);
} else {
    file_put_contents($enPath, implode('', $out));
}

echo "Modules: " . implode(', ', $modules) . "\n";
echo "Keys in views: " . count($keys) . "\n";
echo "Map entries applied: " . count($map) . "\n";
