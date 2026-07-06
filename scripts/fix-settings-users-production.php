<?php
/**
 * Fix setting, users, roles placeholders and sync EN for production/dashboard/settings modules.
 */

$settingKeys = [
    'Entrer Nom ' => ['fr' => 'Entrer Nom ', 'en' => 'Enter name', 'ki' => 'Andika izina'],
    'Entrer NIF' => ['fr' => 'Entrer NIF', 'en' => 'Enter TIN', 'ki' => 'Andika NIF'],
    'Entrer RC' => ['fr' => 'Entrer RC', 'en' => 'Enter RC', 'ki' => 'Andika RC'],
    'Entrer Secteur Activite ' => ['fr' => 'Entrer Secteur Activite ', 'en' => 'Enter activity sector', 'ki' => 'Andika urwego rw\'ibikorwa'],
    'Entrer la forme Juridique' => ['fr' => 'Entrer la forme Juridique', 'en' => 'Enter legal form', 'ki' => 'Andika uburyo bwemewe'],
    'Entrer Province' => ['fr' => 'Entrer Province', 'en' => 'Enter province', 'ki' => 'Andika intara'],
    'Entrer Commune ' => ['fr' => 'Entrer Commune ', 'en' => 'Enter municipality', 'ki' => 'Andika komine'],
    'Entrer Zone ' => ['fr' => 'Entrer Zone ', 'en' => 'Enter zone', 'ki' => 'Andika akarere'],
    'Entrer Quartier' => ['fr' => 'Entrer Quartier', 'en' => 'Enter neighborhood', 'ki' => 'Andika umudugudu'],
    'Entrer Rue ' => ['fr' => 'Entrer Rue ', 'en' => 'Enter street', 'ki' => 'Andika umuhanda'],
    'Entrer code Postal' => ['fr' => 'Entrer code Postal', 'en' => 'Enter postal code', 'ki' => 'Andika kode y\'iposita'],
    'Entrer telephone 1' => ['fr' => 'Entrer telephone 1', 'en' => 'Enter phone 1', 'ki' => 'Andika telefone 1'],
    'Entrer Telephone 2 ' => ['fr' => 'Entrer Telephone 2 ', 'en' => 'Enter phone 2', 'ki' => 'Andika telefone 2'],
    'Entrer Email' => ['fr' => 'Entrer Email', 'en' => 'Enter email', 'ki' => 'Andika imeyili'],
    'Entrer Developpeur' => ['fr' => 'Entrer Developpeur', 'en' => 'Enter developer', 'ki' => 'Andika uwateguye'],
    'Entrer Nbre des lignes' => ['fr' => 'Entrer Nbre des lignes', 'en' => 'Enter number of lines', 'ki' => 'Andika umubare w\'imirongo'],
    'Ex: BANQUE COMMERCIALE DU BURUNDI' => ['fr' => 'Ex: BANQUE COMMERCIALE DU BURUNDI', 'en' => 'E.g. COMMERCIAL BANK OF BURUNDI', 'ki' => 'Urugero: BANKI Y\'UBUCURUZI BWA BURUNDI'],
    'Ex: 001-1234567-89' => ['fr' => 'Ex: 001-1234567-89', 'en' => 'E.g. 001-1234567-89', 'ki' => 'Urugero: 001-1234567-89'],
    'Enter Name' => ['fr' => 'Entrer le nom', 'en' => 'Enter name', 'ki' => 'Andika izina'],
    'Enter Email' => ['fr' => 'Entrer l\'email', 'en' => 'Enter email', 'ki' => 'Andika imeyili'],
    'Enter Password' => ['fr' => 'Entrer le mot de passe', 'en' => 'Enter password', 'ki' => 'Andika ijambo ry\'ibanga'],
    'Enter a Role Name' => ['fr' => 'Entrer le nom du rôle', 'en' => 'Enter a role name', 'ki' => 'Andika izina ry\'uruhare'],
    'Supprimer ce plan ?' => ['fr' => 'Supprimer ce plan ?', 'en' => 'Delete this plan?', 'ki' => 'Siba iyi gahunda?'],
    'Requête' => ['fr' => 'Requête', 'en' => 'Requisition', 'ki' => 'Ubusabe'],
    'Voir' => ['fr' => 'Voir', 'en' => 'View', 'ki' => 'Reba'],
    'Type to search item...' => ['fr' => 'Tapez pour rechercher un article...', 'en' => 'Type to search item...', 'ki' => 'Andika kugira ngo ushakishe ikintu...'],
];

foreach (['fr', 'en', 'ki'] as $lang) {
    $path = __DIR__ . "/../resources/lang/{$lang}/messages.php";
    $content = file_get_contents($path);
    $insert = '';
    foreach ($settingKeys as $k => $vals) {
        $ek = str_replace("'", "\\'", $k);
        if (strpos($content, "'{$ek}' =>") !== false) {
            if ($lang === 'en') {
                $v = str_replace("'", "\\'", $vals['en']);
                $content = preg_replace("/\t'{$ek}'\s*=>\s*'[^']*(?:\\\\'[^']*)*',/", "\t'{$ek}' => '{$v}',", $content, 1);
            }
            continue;
        }
        $v = str_replace("'", "\\'", $vals[$lang]);
        $insert .= "\t'{$ek}' => '{$v}',\n";
    }
    if ($insert) {
        $content = str_replace("];\n\n\n?>", $insert . "];\n\n\n?>", $content);
    }
    file_put_contents($path, $content);
    echo "Updated $lang\n";
}

// Fix setting blades HTML placeholders
$dirs = [
    __DIR__ . '/../resources/views/backend/pages/setting',
    __DIR__ . '/../resources/views/backend/pages/users',
    __DIR__ . '/../resources/views/backend/pages/roles',
];
$fixed = 0;
foreach ($dirs as $dir) {
    if (!is_dir($dir)) continue;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($it as $file) {
        if (!$file->isFile() || substr($file->getFilename(), -10) !== '.blade.php') continue;
        $content = file_get_contents($file->getPathname());
        $orig = $content;
        foreach (array_keys($settingKeys) as $ph) {
            if (in_array($ph, ['Supprimer ce plan ?', 'Requête', 'Voir', 'Type to search item...'])) continue;
            $content = str_replace(
                'placeholder="' . $ph . '"',
                'placeholder="@lang(\'messages.' . str_replace("'", "\\'", $ph) . '\')"',
                $content
            );
        }
        $content = str_replace(
            "confirm('Are you sure you want to delete this item? This action is irreversible.')",
            "confirm(" . '@json(__("messages.delete_confirm_irreversible"))' . ")",
            $content
        );
        if ($content !== $orig) {
            file_put_contents($file->getPathname(), $content);
            $fixed++;
            echo 'Fixed: ' . $file->getFilename() . "\n";
        }
    }
}

// Fix setting JS bank placeholders
$bankI18n = [
    'create' => __DIR__ . '/../resources/views/backend/pages/setting/create.blade.php',
    'edit' => __DIR__ . '/../resources/views/backend/pages/setting/edit.blade.php',
];
foreach ($bankI18n as $name => $path) {
    $content = file_get_contents($path);
    if (strpos($content, 'settingBankI18n') !== false) continue;
    $inject = "    var settingBankI18n = {\n        bankExample: @json(__('messages.Ex: BANQUE COMMERCIALE DU BURUNDI')),\n        accountExample: @json(__('messages.Ex: 001-1234567-89'))\n    };\n";
    $content = preg_replace('/(\$\("#add_bank"\)\.click\(function\(\)\{)/', $inject . '$1', $content, 1)
        ?: preg_replace('/(\$\(\'#add_bank\'\)\.click\(function\(\)\{)/', $inject . '$1', $content, 1);
    $content = str_replace(
        "placeholder=\"Ex: BANQUE COMMERCIALE DU BURUNDI\"",
        'placeholder="@lang(\'messages.Ex: BANQUE COMMERCIALE DU BURUNDI\')"',
        $content
    );
    $content = str_replace(
        "placeholder=\"Ex: 001-1234567-89\"",
        'placeholder="@lang(\'messages.Ex: 001-1234567-89\')"',
        $content
    );
    $content = str_replace(
        "'<input type=\"text\" class=\"form-control\" name=\"bank_name[]\" placeholder=\"Ex: BANQUE COMMERCIALE DU BURUNDI\"></div></div>'",
        "'<input type=\"text\" class=\"form-control\" name=\"bank_name[]\" placeholder=\"' + settingBankI18n.bankExample + '\"></div></div>'",
        $content
    );
    $content = str_replace(
        "'<input type=\"text\" class=\"form-control\" name=\"account_number[]\" placeholder=\"Ex: 001-1234567-89\"></div></div>'",
        "'<input type=\"text\" class=\"form-control\" name=\"account_number[]\" placeholder=\"' + settingBankI18n.accountExample + '\"></div></div>'",
        $content
    );
    file_put_contents($path, $content);
    echo "Patched setting $name JS\n";
}

echo "Blade files fixed: $fixed\n";
