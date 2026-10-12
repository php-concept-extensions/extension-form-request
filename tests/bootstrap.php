<?php declare(strict_types=1);

$candidates = array_values(array_filter([
    getenv('EXTENSION_TEST_AUTOLOAD') ?: null,
    dirname(__DIR__) . '/vendor/autoload.php',      // standalone package install
    dirname(__DIR__, 3) . '/vendor/autoload.php',   // monorepo: packages/{name}/tests → root
]));

$autoload = null;
foreach ($candidates as $candidate) {
    if (is_file($candidate)) {
        $autoload = $candidate;
        break;
    }
}

if ($autoload === null) {
    throw new RuntimeException('Composer autoload not found (package vendor/ or monorepo root vendor/).');
}

$loader = require $autoload;
$loader->addPsr4('Tests\\', __DIR__ . '/');
