<?php

/*
 * Copyright (c) 2018 Armbreaker Developers.
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

$config = [];

// this is passed on to DBAL.
$config['database'] = 'mysqli://x:x@y/z';

// switch to ENTITYTYPE_SCRAPER as needed.
$config['type'] = Armbreaker\ArmbreakerEntity::ENTITYTYPE_MASTER;

// TODO: support SQS queues at some point. For now, this is unused.
$config['queue'] = Armbreaker\ArmbreakerEntity::QUEUE_INTERNAL;

// Set logging level. TODO: use this
$config['logging'] = Monolog\Logger::INFO;

// Host ID for queueing. 0-255, must be unique per instance of armbreaker.
$config['id'] = 0;

// Presently unused.
$config['sqs']    = [
    'version'     => 'latest',
    'region'      => 'eu-west-1',
    'credentials' => [
        'key'    => 'hackme',
        'secret' => 'hackme',
    ],
];
$config['sqsURL'] = "https://sqs.eu-west-1.amazonaws.com/xxx/yyy";
