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
    $run = ConfigFactory::get('type');
    if ($run == ArmbreakerEntity::ENTITYTYPE_MASTER) {
        $ab = new ArmbreakerMaster();
    } elseif ($run == ArmbreakerEntity::ENTITYTYPE_SCRAPER) {
        $ab = new ArmbreakerScraper();
    }
} catch (\Throwable $e) {
    $code = $e->getResponse()->getStatusCode();
    $line = $e->getResponse()->getReasonPhrase();
    Log::l()->addError("Exception thrown!", ['resp' => $code . " " . $line, 'exception' => $e]);
}
