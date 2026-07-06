<?php

$module = $argv[1] ?? null;
if (!$module) {
    echo "Usage: php audit-module-i18n.php <module-folder>\n";
    echo "Example: php audit-module-i18n.php sales_order\n";
    exit(1);
}

$viewsDir = __DIR__ . '/../resources/views/backend/pages/' . $module;
if (!is_dir($viewsDir)) {
    echo "Module not found: $viewsDir\n";
    exit(1);
}

$en = include __DIR__ . '/../resources/lang/en/messages.php';
$fr = include __DIR__ . '/../resources/lang/fr/messages.php';

$keys = [];
$hardcoded = [];
$brokenJs = [];

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsDir));
foreach ($iterator as $file) {
    if (!$file->isFile() || substr($file->getFilename(), -10) !== '.blade.php') {
        continue;
    }
    $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($viewsDir) + 1));
    $content = file_get_contents($file->getPathname());
    $lines = explode("\n", $content);

    preg_match_all("/@lang\('messages\.([^']+)'\)/", $content, $m);
    foreach ($m[1] as $k) {
        $keys[$k] = true;
    }
    preg_match_all("/__\('messages\.([^']+)'\)/", $content, $m2);
    foreach ($m2[1] as $k) {
        $keys[$k] = true;
    }

    // Hardcoded French/English in visible text (heuristic)
    foreach ($lines as $num => $line) {
        $trim = trim($line);
        if ($trim === '' || strpos($trim, '@lang') !== false || strpos($trim, '__(') !== false) {
            continue;
        }
        if (strpos($trim, '{{') !== false && strpos($trim, '@lang') === false && preg_match('/>(Modifier|Nouveau|Créer|Voir |Bon de|Facture|Détail|Rapport|Requête|Supprimer|Ajouter)/u', $trim)) {
            $hardcoded[] = ($rel ?: basename($file->getPathname())) . ':' . ($num + 1) . ' ' . substr($trim, 0, 120);
        }
        if (preg_match("/>(Modifier BC|Modifier BL|Créer Bon|Modifier Facture|Bon de commande)</u", $trim)) {
            $hardcoded[] = ($rel ?: basename($file->getPathname())) . ':' . ($num + 1) . ' ' . substr($trim, 0, 120);
        }
        if (preg_match("/confirm\('[^']+'\)/", $trim) || preg_match('/confirm\("[^"]+"\)/', $trim)) {
            if (strpos($trim, '__') === false && strpos($trim, '@json') === false) {
                $hardcoded[] = ($rel ?: basename($file->getPathname())) . ':' . ($num + 1) . ' confirm: ' . substr($trim, 0, 100);
            }
        }
        if (preg_match("/placeholder=['\"](?!@lang)([^'\"]+)['\"]/", $trim, $pm) && !preg_match('/\{\{/', $pm[1])) {
            if (preg_match('/[A-Za-z]{3,}/', $pm[1])) {
                $hardcoded[] = ($rel ?: basename($file->getPathname())) . ':' . ($num + 1) . ' placeholder: ' . $pm[1];
            }
        }
    }

    if (preg_match("/'\+[^;]*@lang\(/", $content) || preg_match("/\"\+[^;]*@lang\(/", $content)) {
        $brokenJs[] = $rel ?: basename($file->getPathname());
    }
}

$untranslated = [];
$frenchEn = [];
foreach (array_keys($keys) as $k) {
    if (!isset($en[$k])) {
        $untranslated[] = $k . ' (MISSING)';
        continue;
    }
    $v = $en[$k];
    if ($v === $k) {
        $untranslated[] = $k;
    } elseif (preg_match('/[àâäéèêëïîôùûüçœæ]/iu', $v)) {
        $frenchEn[] = $k . ' => ' . $v;
    }
}

echo "=== Module: $module ===\n";
echo 'Blade files: ' . iterator_count(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsDir))) . "\n";
echo 'Keys used: ' . count($keys) . "\n";
echo 'EN untranslated (value=key): ' . count($untranslated) . "\n";
echo 'EN still French: ' . count($frenchEn) . "\n";
echo 'Hardcoded strings: ' . count($hardcoded) . "\n";
echo 'Broken @lang in JS: ' . count($brokenJs) . "\n\n";

if ($hardcoded) {
    echo "--- Hardcoded ---\n";
    foreach (array_slice($hardcoded, 0, 40) as $h) {
        echo $h . "\n";
    }
    if (count($hardcoded) > 40) {
        echo '... and ' . (count($hardcoded) - 40) . " more\n";
    }
    echo "\n";
}

if ($frenchEn) {
    echo "--- EN still French (first 30) ---\n";
    foreach (array_slice($frenchEn, 0, 30) as $h) {
        echo $h . "\n";
    }
    echo "\n";
}

if ($untranslated) {
    echo "--- Untranslated EN keys (first 40) ---\n";
    foreach (array_slice($untranslated, 0, 40) as $h) {
        echo $h . "\n";
    }
}
