<?php declare(strict_types=1);

$candidates = array_filter([
    getenv('EXTENSION_TEST_AUTOLOAD') ?: null,
    dirname(__DIR__) . '/vendor/autoload.php',
    dirname(__DIR__, 2) . '/vendor/autoload.php',
]);

$autoload = null;
foreach ($candidates as $candidate) {
    if (is_string($candidate) && is_file($candidate)) {
        $autoload = $candidate;
        break;
    }
}

if ($autoload === null) {
    throw new RuntimeException('Composer autoload not found (package vendor/ or monorepo root vendor/).');
}

$loader = require $autoload;
$loader->addPsr4('Tests\\', __DIR__ . '/');
