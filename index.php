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
    $ab = new ArmbreakerMaster();
} catch (\Throwable $e) {
    var_dump($e);
}
