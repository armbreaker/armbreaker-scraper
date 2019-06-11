<?php
/**
 * Copyright (c) 2019 Armbreaker Developers.
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Armbreaker\WorkUnit;


use Armbreaker\AsyncRequestTrait;
use Armbreaker\ScraperUtilsTrait;
use Armbreaker\WorkUnitInterface;
use Armbreaker\WorkUnitTrait;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Doctrine\DBAL\Connection;
use React\Promise\PromiseInterface;

class PostScraper implements WorkUnitInterface
{
    use AsyncRequestTrait;
    use ScraperUtilsTrait;
    use WorkUnitTrait {
        WorkUnitTrait::__construct as __constructWorkUnitTrait;
    }

    /**
     * @var int
     */
    protected $postID;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var CarbonInterface
     */
    protected $date;

    /**
     * @var CarbonInterface
     */
    protected $lastUpdated;

    public function __construct(Connection $db, $payload)
    {
        $this->__constructWorkUnitTrait($db, $payload);

        $q = $this->db->executeQuery("select * from armbreaker_fics where tid = ?", [$payload->id], ['integer']);
    }

    public function process(): PromiseInterface
    {
        // TODO: Implement process() method.
    }

    public function getActionString(): string
    {
        return "scrapePosts";
    }

    public function isOutdated(): bool
    {
        $info = $this->getDBInfo();

        return (is_null($info) || $info['lastUpdated'] < Carbon::now()->addDays(-2));

    }

    private function getDBInfo(bool $cache = true): ?array
    {
        static $data = null;
        if ($cache && is_array($data)) {
            return $data;
        }
        $res = $this->db->executeQuery("select * from armbreaker_fics where tid = ? limit 1;",
            [$this->payload->id], ['integer']);
        foreach ($res->fetchAll() as $row) {
            $data = $row;
            $data['lastUpdated'] = new Carbon($data['lastUpdated']);
        }
        return $data;
    }

    public function isAlreadyQueued(): bool
    {
        $check = $this->db->executeQuery("select * from armbreaker_queue where json_extract(payload, '$.id') = ?;",
            [$this->payload->id], ['integer']);
        return (count($check->fetchAll()) > 0);
    }
}
