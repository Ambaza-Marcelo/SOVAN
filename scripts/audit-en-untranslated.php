<?php

$en = include __DIR__ . '/../resources/lang/en/messages.php';
$viewsDir = __DIR__ . '/../resources/views/backend/pages';

$keys = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsDir));
foreach ($iterator as $file) {
    if (!$file->isFile() || substr($file->getFilename(), -10) !== '.blade.php') {
        continue;
    }
    $content = file_get_contents($file->getPathname());
    preg_match_all("/@lang\('messages\.([^']+)'\)/", $content, $m);
    foreach ($m[1] as $k) {
        $keys[$k] = true;
    }
    preg_match_all("/__\('messages\.([^']+)'\)/", $content, $m2);
    foreach ($m2[1] as $k) {
        $keys[$k] = true;
    }
}

$sameAsKey = [];
$frenchLooking = [];
foreach (array_keys($keys) as $k) {
    if (!isset($en[$k])) {
        continue;
    }
    $v = $en[$k];
    if ($v === $k) {
        $sameAsKey[] = $k;
    } elseif (preg_match('/[àâäéèêëïîôùûüçœæ]/iu', $v) && !preg_match('/[àâäéèêëïîôùûüçœæ]/iu', $k)) {
        $frenchLooking[] = $k . ' => ' . $v;
    }
}

echo 'Keys used in views: ' . count($keys) . PHP_EOL;
echo 'EN value identical to key (likely untranslated): ' . count($sameAsKey) . PHP_EOL;
echo 'Sample (first 25):' . PHP_EOL;
foreach (array_slice($sameAsKey, 0, 25) as $k) {
    echo '  ' . $k . PHP_EOL;
}
