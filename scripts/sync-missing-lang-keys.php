<?php

$en = include __DIR__ . '/../resources/lang/en/messages.php';
$fr = include __DIR__ . '/../resources/lang/fr/messages.php';
$ki = include __DIR__ . '/../resources/lang/ki/messages.php';

$viewsDirs = [
    __DIR__ . '/../resources/views/backend/pages',
    __DIR__ . '/../resources/views/backend/layouts',
    __DIR__ . '/../resources/views/backend/auth',
];
$keys = [];
foreach ($viewsDirs as $viewsDir) {
    if (!is_dir($viewsDir)) continue;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsDir));
    foreach ($it as $file) {
        if (!$file->isFile() || substr($file->getFilename(), -10) !== '.blade.php') continue;
        $c = file_get_contents($file->getPathname());
        preg_match_all("/@lang\('messages\.([^']+)'\)/", $c, $m);
        foreach ($m[1] as $k) $keys[$k] = true;
        preg_match_all("/__\('messages\.([^']+)'\)/", $c, $m2);
        foreach ($m2[1] as $k) $keys[$k] = true;
    }
}

$addFr = $addKi = $addEn = '';
foreach (array_keys($keys) as $k) {
    if (!isset($en[$k])) {
        $ek = str_replace("'", "\\'", $k);
        $addEn .= "\t'{$ek}' => '{$ek}',\n";
    }
    if (!isset($fr[$k])) {
        $ek = str_replace("'", "\\'", $k);
        $addFr .= "\t'{$ek}' => '{$ek}',\n";
    }
    if (!isset($ki[$k])) {
        $ek = str_replace("'", "\\'", $k);
        $val = isset($en[$k]) ? str_replace("'", "\\'", $en[$k]) : $ek;
        $addKi .= "\t'{$ek}' => '{$val}',\n";
    }
}

foreach (['en' => $addEn, 'fr' => $addFr, 'ki' => $addKi] as $lang => $insert) {
    if ($insert === '') continue;
    $path = __DIR__ . "/../resources/lang/{$lang}/messages.php";
    if (!file_exists($path) || filesize($path) < 100) {
        fwrite(STDERR, "SKIP $lang: file missing or empty\n");
        continue;
    }
    $content = file_get_contents($path);
    if (strpos($content, '];') === false) {
        fwrite(STDERR, "SKIP $lang: invalid format\n");
        continue;
    }
    $content = preg_replace('/\];\s*\?>\s*$/s', $insert . "];\n\n\n?>", $content, 1);
    file_put_contents($path, $content);
    echo "Added to $lang: " . substr_count($insert, "\n") . " keys\n";
}

echo "Done.\n";
