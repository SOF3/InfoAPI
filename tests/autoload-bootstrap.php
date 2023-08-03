<?php

declare(strict_types=1);

// workaround recursive classloading when running phpunit

$dir = "pharynx-tmp-src/src/";
spl_autoload_register(fn($class) => is_file($file = $dir . str_replace("\\", "/", $class, ) . ".php") && include_once $file, prepend: true);
