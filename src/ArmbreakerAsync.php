<?php
/**
 * Copyright (c) 2019 Armbreaker Developers.
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Armbreaker;

use Clue\React\Buzz\Browser;
use Clue\React\Mq\Queue;
use React\EventLoop\LoopInterface;

/**
 * This is the base for our async implementations of Armbreaker stuff. Both the scraper (Shard) and manager
 * (FaerieQueen) extend this.
 */
abstract class ArmbreakerAsync
{
    use ArmbreakerBaseTrait;
    use AsyncRequestTrait;

    /**
     * @var LoopInterface
     */
    protected $loop;

    /**
     *
     * @var EventManager
     */
    protected $eventManager;

    public function __construct(LoopInterface $loop, array $config)
    {
        $this->loop = $loop;
        $this->setupBaseTrait($config);
        $this->eventManager = new EventManager($this);

        $this->buzz = new Browser($loop);
        $this->requestQueue = new Queue(1, null, function ($url) use (&$config) {
            return $this->buzz->get($url, [
                'User-Agent' => 'sylae/armbreaker (https://github.com/sylae/armbreaker)',
                'X-Armbreaker' => sprintf('hostID %s', $config['id']),
            ]);
        });

        $this->eventManager->addEventListener(EventListener::new()->addEvent("ready")->setCallback([
            $this,
            "asyncReadyHandler",
        ]));
    }

    public function asyncReadyHandler()
    {
        $this->l()->info("Connected!");
        $this->eventManager->initializePeriodics();
    }


    public function start()
    {
        $this->loop->addTimer(0.0001, function () {
            $this->eventManager->fire("ready");
        });
        $this->loop->run();
    }

    /**
     * @return LoopInterface
     */
    public function getLoop(): LoopInterface
    {
        return $this->loop;
    }
}
