<?php
$fr = include __DIR__ . '/../resources/lang/fr/messages.php';
$overrides = include __DIR__ . '/en-translations-map.php';

// Pull additional maps from sync-en-module by requiring its map section
$moduleMap = [];
$src = file_get_contents(__DIR__ . '/sync-en-module.php');
if (preg_match('/\$map\s*=\s*\[(.*?)\];/s', $src, $m)) {
    $code = 'return [' . $m[1] . '];';
    $moduleMap = eval($code);
}

$bulk = [];
$src2 = file_get_contents(__DIR__ . '/bulk-fix-en-translations.php');
if (preg_match('/\$bulk\s*=\s*\[(.*?)\];/s', $src2, $m2)) {
    $bulk = eval('return [' . $m2[1] . '];');
}

$doc = [];
$src3 = file_get_contents(__DIR__ . '/sync-en-document.php');
if (preg_match('/\$map\s*=\s*\[(.*?)\];/s', $src3, $m3)) {
    $doc = eval('return [' . $m3[1] . '];');
}

$allMaps = array_merge($moduleMap ?: [], $bulk ?: [], $doc ?: [], $overrides);

$en = [];
foreach ($fr as $k => $frVal) {
    if (isset($allMaps[$k])) {
        $en[$k] = $allMaps[$k];
    } elseif (preg_match('/^[A-Za-z0-9\s\.,;:!?\-\/\(\)\[\]&%#@*+_=\'\"]+$/', $k) && !preg_match('/[àâäéèêëïîôùûüçœæ]/iu', $k)) {
        $en[$k] = $k;
    } else {
        $en[$k] = $frVal;
    }
}
foreach ($allMaps as $k => $v) {
    $en[$k] = $v;
}

$out = "<?php\n\nreturn[\n";
foreach ($en as $k => $v) {
    $ek = str_replace("'", "\\'", $k);
    $ev = str_replace("'", "\\'", (string)$v);
    $out .= "\t'{$ek}' => '{$ev}',\n";
}
$out .= "];\n\n\n?>\n";

file_put_contents(__DIR__ . '/../resources/lang/en/messages.php', $out);

$test = include __DIR__ . '/../resources/lang/en/messages.php';
echo 'Rebuilt EN: ' . count($test) . " keys\n";
echo 'Overrides applied: ' . count($allMaps) . "\n";
