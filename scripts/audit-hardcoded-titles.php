<?php

$viewsDir = __DIR__ . '/../resources/views/backend/pages';
$hardcoded = [];

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsDir));
foreach ($iterator as $file) {
    if (!$file->isFile() || substr($file->getFilename(), -10) !== '.blade.php') {
        continue;
    }
    $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($viewsDir) + 1));
    $lines = file($file->getPathname());
    for ($i = 0; $i < count($lines) - 1; $i++) {
        if (!preg_match("/@section\('title'\)/", $lines[$i])) {
            continue;
        }
        $titleLine = trim($lines[$i + 1]);
        if ($titleLine === '' || $titleLine === '@endsection') {
            continue;
        }
        if (!preg_match('/@lang|__\(|{{\s*__/', $titleLine)) {
            $hardcoded[] = $rel . ' => ' . $titleLine;
        }
    }
}

echo 'Hardcoded titles: ' . count($hardcoded) . PHP_EOL;
foreach ($hardcoded as $h) {
    echo $h . PHP_EOL;
}
