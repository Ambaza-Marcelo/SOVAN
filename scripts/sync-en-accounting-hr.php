<?php
$enPath = __DIR__ . '/../resources/lang/en/messages.php';
$content = file_get_contents($enPath);
$map = [
    'Clôturée sans retour' => 'Closed without return',
    'Clôturée avec retour encours...' => 'Closed with return in progress...',
    'Clôturée avec retour' => 'Closed with return',
    'Pas encore approuvé la sortie de fonds' => 'Cash outflow not yet approved',
    'Aucun fournisseur ou employé associé à cette demande.' => 'No supplier or employee linked to this request.',
    'Numéro de compte' => 'Account number',
    'Compte non défini' => 'Account not defined',
    'Auto-complété depuis la sortie de fonds' => 'Auto-filled from cash outflow',
    'Montant Retourné' => 'Amount returned',
    'Détails retour de fonds' => 'Return fund details',
    'Aucun employé trouvé' => 'No employee found',
    'Montant à payer 1ere tranche' => 'Amount to pay 1st installment',
    'Montant à payer 2eme tranche' => 'Amount to pay 2nd installment',
    'Montant à payer 3eme tranche' => 'Amount to pay 3rd installment',
    'date et Heure de retour prévue' => 'Expected return date and time',
    'Demande de Congé annuel' => 'Annual leave request',
    'Numéro de Compte:' => 'Account number:',
    'Créer un Filière' => 'Create a field of study',
    'à la date de fin de son dernier congé.' => 'on the end date of their last leave.',
    'Enter account name' => 'Enter account name',
    'Enter account number' => 'Enter account number',
    'Enter description' => 'Enter description',
    'Edit account' => 'Edit account',
    'create account settings' => 'Create account settings',
    'Create account setting' => 'Create account setting',
    'Taxes Accounts' => 'Tax accounts',
    'new journal entry' => 'New journal entry',
    'Edit Journal Entry' => 'Edit journal entry',
    'new fund transfer' => 'New fund transfer',
    'edit fund transfer' => 'Edit fund transfer',
    'fund transfer details' => 'Fund transfer details',
    'new advance payment' => 'New advance payment',
    'edit advance payment' => 'Edit advance payment',
    'advance payment details' => 'Advance payment details',
    'new expense' => 'New expense',
    'edit expense' => 'Edit expense',
    'expense details' => 'Expense details',
    'sortie de fonds' => 'Cash outflow',
    'stockin_edit' => 'Edit stock in',
    'stockout_edit' => 'Edit stock out',
];
foreach ($map as $k => $v) {
    $ek = str_replace("'", "\\'", $k);
    $ev = str_replace("'", "\\'", $v);
    if (strpos($content, "'{$ek}' =>") !== false) {
        $content = preg_replace("/\t'{$ek}'\s*=>\s*'[^']*(?:\\\\'[^']*)*',/", "\t'{$ek}' => '{$ev}',", $content, 1);
    }
}
file_put_contents($enPath, $content);
echo 'Accounting/HR EN fixes: ' . count($map) . " entries\n";
