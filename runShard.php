<?php
/**
 * Copyright (c) 2019 Armbreaker Developers.
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Armbreaker;

use Armbreaker\Client\Shard;
use React\EventLoop\Factory;

require_once "vendor/autoload.php";
require_once 'config.php';

if (php_sapi_name() != "cli") {
    die("CLI only!!");
}

$loop = Factory::create();

$shard = new Shard($loop, $config);
$shard->start();
