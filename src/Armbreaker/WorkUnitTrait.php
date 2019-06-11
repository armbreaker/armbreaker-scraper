<?php
/**
 * Copyright (c) 2019 Armbreaker Developers.
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Armbreaker;


use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

trait WorkUnitTrait
{
    /**
     * @var Connection
     */
    private $db;

    private $payload;

    /**
     * @var int
     */
    private $qid;

    /**
     * @var int
     */
    private $publisher;

    /**
     * @var int
     */
    private $claimant;

    /**
     * @var QueueInterface
     */
    private $queue;

    /**
     * @var LoggerInterface
     */
    private $log;

    public function __construct(Connection $db, $payload)
    {
        $this->db = $db;
        $this->payload = $payload;
    }

    public function setPublisher(int $pid)
    {
        $this->publisher = $pid;
    }

    public function setClaimant(int $cid)
    {
        $this->claimant = $cid;
    }

    public function setQueue(QueueInterface $queue)
    {
        $this->queue = $queue;
    }

    public function getQID(): int
    {
        return $this->qid;
    }

    public function setQID(int $qid)
    {
        $this->qid = $qid;
    }

    public function getPayload()
    {
        return $this->payload;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->log = $logger;
    }

    public function __debugInfo()
    {
        return [
            'id' => $this->qid,
            'payload' => $this->payload,
            'publisher' => $this->publisher,
            'claimant' => $this->claimant,
        ];
    }
}
