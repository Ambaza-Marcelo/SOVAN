<?php

$viewsDir = __DIR__ . '/../resources/views/backend/layouts';
$authDir = __DIR__ . '/../resources/views/backend/auth';
$en = include __DIR__ . '/../resources/lang/en/messages.php';
$fr = include __DIR__ . '/../resources/lang/fr/messages.php';

$keys = [];
$hardcoded = [];
$brokenJs = [];
$wrongLang = [];

$dirs = array_filter([$viewsDir, $authDir], 'is_dir');
foreach ($dirs as $dir) {
    $base = $dir;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($it as $file) {
        if (!$file->isFile() || substr($file->getFilename(), -10) !== '.blade.php') continue;
        $rel = str_replace('\\', '/', substr($file->getPathname(), strlen(dirname($dir, 2)) + 1));
        $content = file_get_contents($file->getPathname());
        $lines = explode("\n", $content);

        preg_match_all("/@lang\('messages\.([^']+)'\)/", $content, $m);
        foreach ($m[1] as $k) $keys[$k] = true;
        preg_match_all("/__\('messages\.([^']+)'\)/", $content, $m2);
        foreach ($m2[1] as $k) $keys[$k] = true;

        preg_match_all("/@lang\('(?!messages\.)([^']+)'\)/", $content, $wm);
        foreach ($wm[1] as $k) $wrongLang[] = "$rel: @lang('$k')";

        foreach ($lines as $num => $line) {
            $trim = trim($line);
            if ($trim === '' || strpos($trim, '@lang') !== false || strpos($trim, '__(') !== false) continue;
            if (strpos($trim, '{{') !== false) continue;
            if (preg_match('/>(Modifier|Nouveau|Créer|Filtrer|Réinitialiser|Affichage|Importations|vous avez|maintenant|Aperçu|Choisir|Choisissez|Export Excel|Date début|Date fin|Fermer)/u', $trim)) {
                $hardcoded[] = $rel . ':' . ($num + 1) . ' ' . substr($trim, 0, 120);
            }
            if (preg_match("/placeholder=['\"](?!@lang)([^'\"]+)['\"]/", $trim, $pm) && preg_match('/[àâäéèêëïîôùûüçÀ-ÿ]/u', $pm[1])) {
                $hardcoded[] = $rel . ':' . ($num + 1) . ' placeholder: ' . $pm[1];
            }
            if (preg_match("/confirm\('[^']+'\)/", $trim) && strpos($trim, '@json') === false) {
                $hardcoded[] = $rel . ':' . ($num + 1) . ' confirm';
            }
        }

        if (preg_match("/'\+[^;]*@lang\(/", $content) || preg_match("/\"\+[^;]*@lang\(/", $content)) {
            $brokenJs[] = $rel;
        }
        if (preg_match("/textContent = '[^']+' \+ /", $content) && strpos($content, '@json') === false) {
            $hardcoded[] = $rel . ' JS string concat';
        }
    }
}

$missingEn = $missingFr = [];
foreach (array_keys($keys) as $k) {
    if (!isset($en[$k])) $missingEn[] = $k;
    if (!isset($fr[$k])) $missingFr[] = $k;
}

echo "=== Layouts + Auth i18n ===\n";
echo "Keys used: " . count($keys) . "\n";
echo "Missing EN: " . count($missingEn) . "\n";
echo "Missing FR: " . count($missingFr) . "\n";
echo "Hardcoded: " . count($hardcoded) . "\n";
echo "Wrong @lang (no messages.): " . count($wrongLang) . "\n";
echo "Broken JS @lang: " . count($brokenJs) . "\n";

if ($missingEn) {
    echo "\n--- Missing EN ---\n";
    foreach (array_slice($missingEn, 0, 30) as $k) echo "$k\n";
}
if ($hardcoded) {
    echo "\n--- Hardcoded ---\n";
    foreach ($hardcoded as $h) echo "$h\n";
}
if ($wrongLang) {
    echo "\n--- Wrong @lang ---\n";
    foreach ($wrongLang as $w) echo "$w\n";
}
