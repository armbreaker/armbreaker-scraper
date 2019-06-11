<?php
/**
 * Copyright (c) 2019 Armbreaker Developers.
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Armbreaker;


use Armbreaker\WorkUnit\ChapterScraper;
use Armbreaker\WorkUnit\FrontpageScraper;
use Armbreaker\WorkUnit\PostScraper;
use Doctrine\DBAL\Connection;
use Exception;

class MySQLQueue implements QueueInterface
{
    /**
     * @var Connection
     */
    private $db;

    /**
     * @var array
     */
    private $config;

    public function __construct(Connection $db, array $config)
    {
        $this->db = $db;
        $this->config = $config;
    }

    public function getPendingJobCount(): int
    {
        $res = $this->db->executeQuery("select count(*) as c from armbreaker_queue where isClaimed = false and isComplete = false;");
        return (int) $res->fetch()['c'];
    }

    public function getJob(bool $claim = true): ?WorkUnitInterface
    {
        $res = $this->db->executeQuery("select * from armbreaker_queue where isClaimed = false and isComplete = false limit 1;");

        if (is_null($work = $res->fetch()) || !is_string($work['action'] ?? false)) {
            return null;
        }

        switch ($work['action']) {
            case "scrapeFrontpage":
                $unit = new FrontpageScraper($this->db, json_decode(null));
                break;
            case "scrapeChapter":
                $unit = new ChapterScraper($this->db, json_decode($work['payload']));
                break;
            case "scrapePosts":
                $unit = new PostScraper($this->db, json_decode($work['payload']));
                break;
            default:
                return null;
        }

        // sanity check
        if (!$unit instanceof WorkUnitInterface) {
            throw new Exception("WorkUnitInterface not created!");
        }
        $unit->setPublisher($work['publisher']);
        $unit->setQueue($this);
        $unit->setQID($work['qid']);

        if ($claim) {
            $unit->setClaimant($this->config['id']);
            $this->db->executeQuery("update armbreaker_queue set isClaimed = true, claimant = ? where qid = ?",
                [$this->config['id'], $work->getQID()], ['integer', 'integer']);
        }
        return $unit;
    }

    public function completeJob(WorkUnitInterface $job)
    {
        $this->db->executeQuery("update armbreaker_queue set isComplete = true where qid = ?",
            [$job->getQID()], ['integer']);
    }

    public function addJob(WorkUnitInterface $job)
    {
        $this->db->executeQuery("insert into armbreaker_queue (publisher, action, payload) values (?, ?, ?)",
            [$this->config['id'], $job->getActionString(), json_encode($job->getPayload())],
            ['integer', 'string', 'string']);
    }
}
