<?php
/**
 * Sync EN translations for document print blades.
 */
$enPath = __DIR__ . '/../resources/lang/en/messages.php';
$content = file_get_contents($enPath);

$map = [
    'Désignation' => 'Description',
    'Reçu' => 'Received',
    'Description:' => 'Description:',
    'Remis par (Signature)' => 'Handed over by (Signature)',
    'Nom :' => 'Name:',
    'Réceptionné par (Signature)' => 'Received by (Signature)',
    'No' => 'No.',
    'Ingredients' => 'Ingredients',
    'Période:' => 'Period:',
    'Magasin:' => 'Store:',
    'Signature:' => 'Signature:',
    'Date' => 'Date',
    'Magasin' => 'Store',
    'Qté init.' => 'Initial qty.',
    'Qté entrée' => 'Qty in',
    'Qté sortie' => 'Qty out',
    'Qté finale' => 'Final qty.',
    'Type' => 'Type',
    'Document' => 'Document',
    'Date:' => 'Date:',
    'Réceptionniste:' => 'Receiver:',
    'TOTAL (qté théorique / physique)' => 'TOTAL (theoretical / physical qty)',
    'Quantité commandée' => 'Quantity ordered',
    'Quantité reçue' => 'Quantity received',
    'Réf. facture concernée :' => 'Related invoice ref.:',
    'Coût total estimé:' => 'Total estimated cost:',
    'INVENTAIRE AU STOCK INTERMÉDIAIRE DES MATIÈRES PREMIÈRES' => 'INTERMEDIATE RAW MATERIALS STORE INVENTORY',
    'TOTAL (qté actuelle / nouvelle)' => 'TOTAL (current / new qty)',
    'RAPPORT DU STOCK INTERMÉDIAIRE DES MATIÈRES PREMIÈRES' => 'INTERMEDIATE RAW MATERIALS STOCK REPORT',
    'ÉTAT DE STOCK INTERMÉDIAIRE DES MATIÈRES PREMIÈRES' => 'INTERMEDIATE RAW MATERIALS STOCK STATUS',
    'ÉTAT DE GROS STOCK DES MATIÈRES PREMIÈRES' => 'BIG STORE RAW MATERIALS STATUS',
    'Quantité demandée' => 'Quantity requested',
    'ÉTAT DE PETIT STOCK DES MATIÈRES PREMIÈRES' => 'SMALL STORE RAW MATERIALS STATUS',
    'Quantité' => 'Quantity',
    'Unité' => 'Unit',
    'Prix unitaire' => 'Unit price',
    'Montant' => 'Amount',
    'Total' => 'Total',
    'TOTAL' => 'TOTAL',
    'TOTAL GÉNÉRAL' => 'GRAND TOTAL',
    'TOTAL GÉNÉRAL:' => 'GRAND TOTAL:',
    'Sous-total' => 'Subtotal',
    'TVA' => 'VAT',
    'Remise' => 'Discount',
    'Net à payer' => 'Net payable',
    'Arrêté la présente facture à la somme de' => 'This invoice is settled at the amount of',
    'Arrêté le présent bon à la somme de' => 'This note is settled at the amount of',
    'Fait à' => 'Done at',
    'Le' => 'On',
    'Client:' => 'Client:',
    'Fournisseur:' => 'Supplier:',
    'N°' => 'No.',
    'Référence' => 'Reference',
    'Objet' => 'Subject',
    'Motif' => 'Reason',
    'Observations' => 'Observations',
    'Validé par' => 'Validated by',
    'Approuvé par' => 'Approved by',
    'Préparé par' => 'Prepared by',
    'Demandeur' => 'Requester',
    'Bénéficiaire' => 'Beneficiary',
    'Compte' => 'Account',
    'Débit' => 'Debit',
    'Crédit' => 'Credit',
    'Libellé' => 'Label',
    'Montant en lettres' => 'Amount in words',
    'Code article' => 'Item code',
    'Désignation article' => 'Item description',
    'Stock théorique' => 'Theoretical stock',
    'Stock physique' => 'Physical stock',
    'Écart' => 'Variance',
    'Commentaire' => 'Comment',
    'État' => 'Status',
    'Auteur' => 'Author',
    'Imprimé le' => 'Printed on',
    'Page' => 'Page',
    'RAPPORT GÉNÉRÉ LE' => 'REPORT GENERATED ON',
];

$added = 0;
$updated = 0;
foreach ($map as $k => $v) {
    $ek = str_replace("'", "\\'", $k);
    $ev = str_replace("'", "\\'", $v);
    $pattern = "/\t'{$ek}'\s*=>\s*'[^']*(?:\\\\'[^']*)*',/";
    if (preg_match($pattern, $content)) {
        $content = preg_replace($pattern, "\t'{$ek}' => '{$ev}',", $content);
        $updated++;
    } else {
        $line = "\t'{$ek}' => '{$ev}',\n";
        if (strpos($content, "'{$ek}' =>") === false) {
            $content = str_replace("];\n\n\n?>", $line . "];\n\n\n?>", $content);
            $added++;
        }
    }
}

file_put_contents($enPath, $content);
echo "Document EN: updated $updated, added $added keys\n";
