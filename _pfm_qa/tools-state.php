<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$found = [];
foreach (App\Models\Tool::query()->whereIn('slug', App\Support\Pfm\PfmCatalog::toolKeys())->get() as $t) {
    $found[$t->slug] = $t;
    echo str_pad($t->slug, 34).' launch='.($t->canLaunch() ? 'Y' : 'N').' '.json_encode(array_intersect_key($t->getAttributes(), array_flip(['status', 'is_active', 'is_enabled', 'launch_state', 'visibility']))).PHP_EOL;
}
foreach (App\Support\Pfm\PfmCatalog::toolKeys() as $k) {
    if (! isset($found[$k])) {
        echo "MISSING in DB: $k".PHP_EOL;
    }
}
