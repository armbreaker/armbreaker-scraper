<?php
/**
 * Copyright (c) 2019 Armbreaker Developers.
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Armbreaker\Client;


use Armbreaker\ArmbreakerAsync;
use Armbreaker\EventListener;
use Armbreaker\WorkUnit\ChapterScraper;
use Armbreaker\WorkUnit\FrontpageScraper;
use React\EventLoop\LoopInterface;

/**
 * Overseer of all the shards. Distributes jobs, spins up new workers (shards) and basically acts like hot shit all the
 * time.
 */
class FaerieQueen extends ArmbreakerAsync
{
    /**
     * @var int
     */
    private $shardSpinupCooldown;

    public function __construct(LoopInterface $loop, array $config)
    {
        parent::__construct($loop, $config);

        $this->eventManager->addEventListener(EventListener::new()->setPeriodic(5)->setCallback([
            $this,
            "loop",
        ]));
        $this->eventManager->addEventListener(EventListener::new()->setPeriodic(300)->setCallback([
            $this,
            "sbFrontPageChecker",
        ]));
        $this->eventManager->addEventListener(EventListener::new()->setPeriodic(30)->setCallback([
            $this,
            "oldPostsChecker",
        ]));
    }

    public function loop()
    {
        $jobs = $this->queue->getPendingJobCount();
        $this->l()->info("$jobs pending jobs");
        if ($jobs > 100 && $this->shardSpinupCooldown > time()) {
            $this->shardSpinupCooldown = time() + 300; // todo: timeout configure
            $this->l()->info("Spinning up a new worker shard.");
            // todo: spin up new worker shards.
        }
    }

    public function sbFrontPageChecker()
    {
        $this->l()->info("Checking frontpage.");
        $job = new FrontpageScraper($this->db, []);
        $job->setQueue($this->queue);
        $job->setLogger($this->log);
        $job->setReqClient($this->requestQueue);
        if ($job->isOutdated()) {
            $this->queue->addJob($job);
        }
    }

    public function oldPostsChecker()
    {
        $this->l()->info("Checking for posts that need to be updated.");

        // todo: config set limit and interval
        $res = $this->db->executeQuery("select * from armbreaker_posts where lastUpdated < DATE_SUB(NOW(), interval 48 hour) limit 1;");
        foreach ($res->fetchAll() as $post) {
            $job = new ChapterScraper($this->db, $post);
            if ($job->isOutdated()) {
                $this->queue->addJob($job);
            }
        }

    }
}
