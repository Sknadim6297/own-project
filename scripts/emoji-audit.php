<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$dirs = [
    'resources/views',
    'public/frontend/assets/js',
    'public/frontend/assets/css',
    'app/Support',
    'app/Services',
    'app/Helpers',
    'database/seeders',
    'resources/js',
];

$allowlist = [
    'app/Support/Icons/YuktioneIconRegistry.php',
    'scripts/emoji-audit.php',
    'tests/Feature/UiIconConsistencyTest.php',
    'docs/GLOBAL_ICON_OPTIMIZATION_REPORT.md',
];

$emoji = '/[\x{1F300}-\x{1FAFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{FE0F}\x{200D}]/u';
$count = 0;
$allowedCount = 0;
$files = [];

foreach ($dirs as $d) {
    $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $d);
    if (! is_dir($path)) {
        continue;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if (! $f->isFile()) {
            continue;
        }
        $name = $f->getFilename();
        if (! preg_match('/\.(php|js|css|json|blade\.php)$/i', $name) && ! str_ends_with($name, '.blade.php')) {
            continue;
        }
        $c = @file_get_contents($f->getPathname());
        if ($c === false) {
            continue;
        }
        if (preg_match_all($emoji, $c, $m)) {
            $n = count($m[0]);
            $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($root) + 1));
            if (in_array($rel, $allowlist, true)) {
                $allowedCount += $n;
                continue;
            }
            $count += $n;
            $files[$rel] = ($files[$rel] ?? 0) + $n;
        }
    }
}

arsort($files);
echo "UNAPPROVED={$count} FILES=".count($files)." ALLOWLISTED={$allowedCount}".PHP_EOL;
foreach (array_slice($files, 0, 50, true) as $f => $n) {
    echo "{$n}\t{$f}".PHP_EOL;
}
