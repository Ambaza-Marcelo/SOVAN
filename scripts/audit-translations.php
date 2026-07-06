<?php

$viewsDirs = [
    __DIR__ . '/../resources/views/backend/pages',
    __DIR__ . '/../resources/views/backend/layouts',
    __DIR__ . '/../resources/views/backend/auth',
];
$en = include __DIR__ . '/../resources/lang/en/messages.php';
$fr = include __DIR__ . '/../resources/lang/fr/messages.php';

$keys = [];
$brokenJsLang = [];
$hardcodedSections = [];

foreach ($viewsDirs as $viewsDir) {
    if (!is_dir($viewsDir)) continue;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsDir));
    foreach ($iterator as $file) {
        if (!$file->isFile() || substr($file->getFilename(), -10) !== '.blade.php') {
            continue;
        }
        $path = $file->getPathname();
        $rel = str_replace('\\', '/', substr($path, strlen($viewsDir) + 1));
        $prefix = basename($viewsDir);
        $content = file_get_contents($path);

        if (preg_match_all("/@lang\('messages\.([^']+)'\)/", $content, $m)) {
            foreach ($m[1] as $k) {
                $keys[$k] = true;
            }
        }
        if (preg_match_all("/__\('messages\.([^']+)'\)/", $content, $m)) {
            foreach ($m[1] as $k) {
                $keys[$k] = true;
            }
        }

        if (preg_match("/placeholder=['\"]@lang\(/", $content) || preg_match("/'\+.*@lang\(/", $content)) {
            $brokenJsLang[] = $prefix . '/' . $rel;
        }
    }
}

$missingEn = [];
$missingFr = [];
foreach (array_keys($keys) as $k) {
    if (!array_key_exists($k, $en)) {
        $missingEn[] = $k;
    }
    if (!array_key_exists($k, $fr)) {
        $missingFr[] = $k;
    }
}
sort($missingEn);
sort($missingFr);

echo "Keys used in backend (pages+layouts+auth): " . count($keys) . PHP_EOL;
echo "Missing in en: " . count($missingEn) . PHP_EOL;
echo "Missing in fr: " . count($missingFr) . PHP_EOL;
echo "Broken @lang in JS (" . count($brokenJsLang) . "): " . implode(', ', $brokenJsLang) . PHP_EOL;
echo PHP_EOL . "=== Missing EN keys ===" . PHP_EOL;
foreach ($missingEn as $k) {
    echo $k . PHP_EOL;
}
echo PHP_EOL . "=== Missing FR keys ===" . PHP_EOL;
foreach ($missingFr as $k) {
    echo $k . PHP_EOL;
}
