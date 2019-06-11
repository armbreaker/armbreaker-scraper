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
use CharlotteDunois\Collect\Collection;
use Psr\Http\Message\ResponseInterface;
use QueryPath\DOMQuery;
use React\Promise\PromiseInterface;
use Throwable;

class FrontpageScraper implements WorkUnitInterface
{
    use WorkUnitTrait;
    use AsyncRequestTrait;
    use ScraperUtilsTrait;

    public function process(): PromiseInterface
    {
        return $this->get("https://forums.spacebattles.com/forums/worm.115/")->then(function (
            ResponseInterface $response
        ) {
            $string = (string) $response->getBody()->getContents();

            $posts = $this->parseFrontPage($string);

            foreach ($posts as $post) {
                $job = new PostScraper($this->db, $post);
                if ($job->isOutdated() && !$job->isAlreadyQueued()) {
                    $this->log->info("Adding PostScraper job to queue", (array) $job->getPayload());
                    $this->queue->addJob($job);
                } else {
                    $this->log->debug("No need to queue PostScraper job", (array) $job->getPayload());
                }
            }
            return true;
        }, function (Throwable $e) {
            $this->log->error($e->getMessage(), ['exception' => $e]);
            return false;
        });
    }

    private function parseFrontPage(string $string): Collection
    {
        $data = html5qp($string);
        $items = $data->find('li.discussionListItem');
        $collect = new Collection([]);

        /** @var DOMQuery $item */
        foreach ($items as $item) {
            try {
                $x = (object) [
                    'id' => (int) str_replace("thread-", "", $item->attr("id")),
                    'title' => trim($item->find('h3')->text()),
                    'threadTime' => $this->unfuckDates($item->find(".startDate .DateTime")),
                    'author' => [
                        'id' => $this->unfuckUserID($item->find('.posterDate a.username')->attr("href")),
                        'name' => $item->find('.posterDate a.username')->text(),
                    ],
                ];
                if (is_null($x->threadTime)) {
                    continue;
                }
                $collect->set($x->id, $x);
            } catch (Throwable $e) {
                $this->log->warning($e->getMessage(), ['exception' => $e]);
            }
        }

        return $collect;
    }

    public function isOutdated(): bool
    {
        return true;
    }

    public function isAlreadyQueued(): bool
    {
        $check = $this->db->executeQuery("select * from armbreaker_queue where action = ?;",
            [$this->getActionString()], ['string']);
        return (count($check->fetchAll()) > 0);
    }

    public function getActionString(): string
    {
        return "scrapeFrontpage";
    }
}
