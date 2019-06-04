<?php

/**
 * Copyright (c) 2019 Armbreaker Developers.
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

// For use in non-Docker contexts, copy this to config.php and change the values as needed.
// With Docker, this will eventually use secrets (TODO)

$config = [];

// this is passed on to DBAL. If unset, grab from secret 'armbreaker_database'
$config['database'] = 'mysqli://x:x@y/z';

$config['GithubPullToken'] = "changeme"; // used for pulling from github.

$config['id'] = 0; // id of this entity, 0..255
