<?php
/**
 * Copyright (c) 2019 Armbreaker Developers.
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Armbreaker;

use CharlotteDunois\Collect\Collection;
use React\Promise\PromiseInterface as Promise;
use function React\Promise\all;

class EventManager
{
    /**
     * @var ArmbreakerAsync
     */
    protected $client;

    /**
     *
     * @var Collection
     */
    protected $events;

    public function __construct(ArmbreakerAsync $client)
    {
        $this->client = $client;
        $this->events = new Collection();
        $this->client->l()->info("[AEM] Armbreaker EventManager initialized.");
    }

    public function addEventListener(EventListener $listener): int
    {
        $id = $this->getEventID();
        $this->events->set($id, $listener);
        $this->client->l()->debug("[AEM] Added event $id");
        return $id;
    }

    private function getEventID(): int
    {
        while (true) {
            $id = random_int(PHP_INT_MIN, PHP_INT_MAX);
            if ($this->events->has($id)) {
                continue;
            } else {
                return $id;
            }
        }
    }

    public function fire(string $type, $data = null): Promise
    {
        $events = $this->returnMatchingEvents($type);
        $this->client->l()->debug("[AEM] Found " . $events->count() . " matching events.");
        $values = $events->map(function (EventListener $v) use ($data) {
            if (is_null($data)) {
                $data = $this->client;
            }
            return $v->getCallback()($data);
        });
        return all($values);
    }

    private function returnMatchingEvents(string $type): Collection
    {
        return $this->events->filter(function (EventListener $v) use ($type) {
            return $v->match($type);
        });
    }

    public function initializePeriodics()
    {
        $periodics = $this->events->filter(function (EventListener $v) {
            return ($v->getPeriodic() > 0);
        })->groupBy(function (EventListener $v) {
            return $v->getPeriodic();
        });
        foreach ($periodics as $interval => $events) {
            $timing = $interval / count($events);
            $this->client->l()->debug("[AEM] Periodic interval {$interval}s has " . count($events) . " slots.");
            $this->client->getLoop()->addPeriodicTimer($timing, function () use ($interval, $events) {
                static $phase = [];
                if (!array_key_exists($interval, $phase)) {
                    $phase[$interval] = 0;
                }
                $fire = $phase[$interval] % count($events);
                $this->client->l()->debug("[AEM] Firing periodic {$interval}s phase $fire/" . count($events));
                $events[$fire]->getCallback()($this->client);
                $phase[$interval]++;
            });
        }
    }
}
