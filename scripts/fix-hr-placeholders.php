<?php
/**
 * Fix HR hardcoded placeholders -> @lang()
 */

$hrKeys = [
    'Entrer Nom ' => ['fr' => 'Entrer Nom ', 'en' => 'Enter name', 'ki' => 'Andika izina'],
    'Entrer NIF' => ['fr' => 'Entrer NIF', 'en' => 'Enter TIN', 'ki' => 'Andika NIF'],
    'Entrer RC' => ['fr' => 'Entrer RC', 'en' => 'Enter RC', 'ki' => 'Andika RC'],
    'Entrer Commune ' => ['fr' => 'Entrer Commune ', 'en' => 'Enter municipality', 'ki' => 'Andika komine'],
    'Entrer Zone' => ['fr' => 'Entrer Zone', 'en' => 'Enter zone', 'ki' => 'Andika akarere'],
    'Entrer Quartier' => ['fr' => 'Entrer Quartier', 'en' => 'Enter neighborhood', 'ki' => 'Andika umudugudu'],
    'Entrer Avenue ' => ['fr' => 'Entrer Avenue ', 'en' => 'Enter avenue', 'ki' => 'Andika avanue'],
    'Entrer Rue' => ['fr' => 'Entrer Rue', 'en' => 'Enter street', 'ki' => 'Andika umuhanda'],
    'Entrer telephone 1' => ['fr' => 'Entrer telephone 1', 'en' => 'Enter phone 1', 'ki' => 'Andika telefone 1'],
    'Entrer Telephone 2 ' => ['fr' => 'Entrer Telephone 2 ', 'en' => 'Enter phone 2', 'ki' => 'Andika telefone 2'],
    'Entrer Email' => ['fr' => 'Entrer Email', 'en' => 'Enter email', 'ki' => 'Andika imeyili'],
    'Entrer Developpeur' => ['fr' => 'Entrer Developpeur', 'en' => 'Enter developer', 'ki' => 'Andika uwateguye'],
    'Entrer Nom Ecole' => ['fr' => 'Entrer Nom Ecole', 'en' => 'Enter school name', 'ki' => 'Andika izina ry\'ishuri'],
    'Entrer Adresse' => ['fr' => 'Entrer Adresse', 'en' => 'Enter address', 'ki' => 'Andika aderesi'],
    'Entrer Nom Filière' => ['fr' => 'Entrer Nom Filière', 'en' => 'Enter field name', 'ki' => 'Andika izina ry\'icyiciro'],
    'Entrer Plafond Impôt' => ['fr' => 'Entrer Plafond Impôt', 'en' => 'Enter tax ceiling', 'ki' => 'Andika urwego rw\'umusoro'],
    'Entrer Plafond Cotisation' => ['fr' => 'Entrer Plafond Cotisation', 'en' => 'Enter contribution ceiling', 'ki' => 'Andika urwego rw\'ingoboka'],
    'Entrer Nbre Jrs Ouvrable' => ['fr' => 'Entrer Nbre Jrs Ouvrable', 'en' => 'Enter working days count', 'ki' => 'Andika iminsi y\'akazi'],
    'Entrer Nbre Jrs Anticipation Congé' => ['fr' => 'Entrer Nbre Jrs Anticipation Congé', 'en' => 'Enter leave anticipation days', 'ki' => 'Andika iminsi y\'uburuhukiro bw\'imbere'],
    'Entrer Nbre Jrs Congé Par mois' => ['fr' => 'Entrer Nbre Jrs Congé Par mois', 'en' => 'Enter leave days per month', 'ki' => 'Andika iminsi y\'uburuhukiro ku kwezi'],
    'Entrer Min Jrs Congé Payé par Mois' => ['fr' => 'Entrer Min Jrs Congé Payé par Mois', 'en' => 'Enter min paid leave days per month', 'ki' => 'Andika iminsi nke y\'uburuhukiro bishyurwa ku kwezi'],
    'Nom Marié(e)' => ['fr' => 'Nom Marié(e)', 'en' => 'Spouse name', 'ki' => 'Izina ry\'uwo bashakanye'],
    'Nombre Enfants' => ['fr' => 'Nombre Enfants', 'en' => 'Number of children', 'ki' => 'Umubare w\'abana'],
];

foreach (['fr', 'en', 'ki'] as $lang) {
    $path = __DIR__ . "/../resources/lang/{$lang}/messages.php";
    $content = file_get_contents($path);
    $insert = '';
    foreach ($hrKeys as $k => $vals) {
        $ek = str_replace("'", "\\'", $k);
        if (strpos($content, "'{$ek}' =>") !== false) {
            continue;
        }
        $v = str_replace("'", "\\'", $vals[$lang]);
        $insert .= "\t'{$ek}' => '{$v}',\n";
    }
    if ($insert) {
        $content = str_replace("];\n\n\n?>", $insert . "];\n\n\n?>", $content);
        file_put_contents($path, $content);
        echo "Added " . substr_count($insert, "\n") . " keys to $lang\n";
    }
}

$hrDir = __DIR__ . '/../resources/views/backend/pages/hr';
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($hrDir));
$fixed = 0;
foreach ($it as $file) {
    if (!$file->isFile() || substr($file->getFilename(), -10) !== '.blade.php') {
        continue;
    }
    $content = file_get_contents($file->getPathname());
    $orig = $content;
    foreach (array_keys($hrKeys) as $placeholder) {
        $ep = str_replace("'", "\\'", $placeholder);
        $content = preg_replace(
            '/placeholder="' . preg_quote($placeholder, '/') . '"/',
            'placeholder="@lang(\'messages.' . str_replace("'", "\\'", $placeholder) . '\')"',
            $content
        );
    }
    // confirm fix for show-paie
    $content = str_replace(
        "confirm('Are you sure you want to delete this item? This action is irreversible.')",
        "confirm(" . '@json(__("messages.delete_confirm_irreversible"))' . ")",
        $content
    );
    if ($content !== $orig) {
        file_put_contents($file->getPathname(), $content);
        $fixed++;
        echo 'Fixed: ' . $file->getPathname() . "\n";
    }
}

echo "HR blades updated: $fixed\n";
