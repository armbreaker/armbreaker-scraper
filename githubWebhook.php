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

function slow_equals(string $a, string $b): bool
{
    $diff = strlen($a) ^ strlen($b);
    for ($i = 0; $i < strlen($a) && $i < strlen($b); $i++) {
        $diff |= ord($a[$i]) ^ ord($b[$i]);
    }
    return $diff === 0;
}
try {
    $our_token   = ConfigFactory::get()['GithubPullToken'] ?? null;
    $their_token = $_REQUEST['webhook-token'] ?? null;

    if (!is_null($our_token) && !is_null($their_token) && slow_equals($out_token, $their_token)) {
        log::l()->addNotice("webhook hit passed authentication, running ./update now!");
        passthru("./update");
    } else {
        http_response_code(403);
        log::l()->addNotice("webhook hit failed authentication!");
        echo ":thonk:";
    }
} catch (\Throwable $e) {
    $code = $e->getResponse()->getStatusCode();
    $line = $e->getResponse()->getReasonPhrase();
    Log::l()->addError("Exception thrown!", ['resp' => $code . " " . $line, 'exception' => $e]);
}
