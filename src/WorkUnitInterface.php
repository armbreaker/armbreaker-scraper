<?php
/**
 * Copyright (c) 2019 Armbreaker Developers.
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Armbreaker;


use Clue\React\Mq\Queue;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use React\Promise\PromiseInterface;

interface WorkUnitInterface
{
    /**
     * WorkUnitInterface constructor.
     *
     * @param Connection $db
     * @param mixed      $payload must be json serializable
     */
    public function __construct(Connection $db, $payload);

    public function process(): PromiseInterface;

    public function setQID(int $qid);

    public function getQID(): int;

    public function setPublisher(int $pid);

    public function setClaimant(int $cid);

    public function setQueue(QueueInterface $queue);

    public function getActionString(): string;

    public function getPayload();

    public function isOutdated(): bool;

    public function setReqQueue(Queue $client);

    public function setLogger(LoggerInterface $logger);
}
