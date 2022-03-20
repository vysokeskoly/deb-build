<?php declare(strict_types=1);

use Composer\Autoload\ClassLoader;

$classLoader = new ClassLoader();

$classLoader->addPsr4('VysokeSkoly\\Build\\', __DIR__ . '/Build');
$classLoader->register();

return $classLoader;
