<?php
/**
 * Copyright (c) 2019 Armbreaker Developers.
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Armbreaker\Client;


use Armbreaker\ArmbreakerAsync;
use Armbreaker\EventListener;
use React\EventLoop\LoopInterface;
use Throwable;

/**
 * Shards do work!
 */
class Shard extends ArmbreakerAsync
{
    /**
     * @var bool
     */
    private $hasJob;

    public function __construct(LoopInterface $loop, array $config)
    {
        parent::__construct($loop, $config);

        $this->eventManager->addEventListener(EventListener::new()->setPeriodic(5)->setCallback([
            $this,
            "loop",
        ]));
        $this->hasJob = false;
    }

    public function loop()
    {
        if ($this->hasJob) {
            return;
        }

        $job = $this->queue->getJob(false);

        if (is_null($job)) {
            return;
        }

        $this->hasJob = true;

        $job->setReqClient($this->requestQueue);
        $job->setLogger($this->log);
        $job->process()->then(function (bool $result) use ($job) {
            var_dump("done", $result);
            $id = $job->getQID();
            if ($result) {
                $this->log->info("Job {$id} completed successfully.");
            } else {
                $this->log->warning("Job {$id} failed!");
            }
            // $this->queue->completeJob($job);
            // $this->hasJob = false;
        }, function (Throwable $e) use ($job) {
            $this->log->warning("Job {$job->getQID()} failed!", ['exception' => $e]);
            // $this->queue->completeJob($job);
            // $this->hasJob = false;
        });
    }
}
