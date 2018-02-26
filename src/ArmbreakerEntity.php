<?php

/*
 * Copyright (c) 2018 Armbreaker Developers.
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Armbreaker;

/**
 * [DESTINATION] [AGREEMENT] [TRAJECTORY] [AGREEMENT]
 *
 * @author sylae and skyyrunner
 */
class ArmbreakerEntity
{
    /**
     * Stores an AWS SQS client. Unused right now :v
     * @var \Aws\Sqs\SqsClient
     */
    protected $sqs;

    /**
     * Reference to the db object for convenience
     * @var \Doctrine\DBAL\Connection
     */
    protected $db;

    /**
     * Hey you like sending web requests yeah?
     * @var \Slim\App
     */
    protected $slim;

    const ENTITYTYPE_MASTER  = 0;
    const ENTITYTYPE_SCRAPER = 1;
    const QUEUE_INTERNAL     = 0;
    const QUEUE_SQS          = 1;

    /**
     * Constructor. Just does some basic setup :v
     */
    public function __construct()
    {
        try {
            $this->sqs  = new \Aws\Sqs\SqsClient(ConfigFactory::get()['sqs']);
            $this->db   = DatabaseFactory::get();
            $this->slim = new \Slim\App();
        } catch (\Throwable $e) {
            var_dump($e);
        }
    }
}
