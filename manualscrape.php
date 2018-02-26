<?php

/*
 * Copyright (c) 2018 Armbreaker Developers.
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Armbreaker;

require_once "vendor/autoload.php";
require_once 'config.php';
// initialization
ConfigFactory::make($config);
new Log();
DatabaseFactory::make();

try {
    if (is_numeric($argv[1] ?? false)) {
        $x = new FicScraper($argv[1]);
    } else {
        throw new \Exception("Usage: php manualscrape.php FIC_ID");
    }
} catch (\Throwable $e) {
    echo $e->getMessage() . PHP_EOL . $e->getFile() . " // Line " . $e->getLine() . PHP_EOL;
}
