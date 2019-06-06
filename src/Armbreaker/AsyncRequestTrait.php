<?php
/**
 * Copyright (c) 2019 Armbreaker Developers.
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Armbreaker;


use Clue\React\Buzz\Browser;
use Clue\React\Mq\Queue;
use React\Promise\PromiseInterface;

trait AsyncRequestTrait
{

    /**
     * @var Browser
     */
    protected $buzz;

    /**
     * @var Queue
     */
    protected $requestQueue;

    public function get(string $url): PromiseInterface
    {
        $q = $this->requestQueue;
        return $q($url);
    }

    public function setReqQueue(Queue $queue)
    {
        $this->requestQueue = $queue;
    }

}
