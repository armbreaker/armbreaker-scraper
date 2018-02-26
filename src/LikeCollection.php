<?php

/*
 * Copyright (c) 2018 Armbreaker Developers.
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Armbreaker;

/**
 * Holds many likes
 *
 * @author sylae and skyyrunner
 */
class LikeCollection implements \Iterator, \Countable, \JsonSerializable
{

    /**
     * @var int
     */
    private $position = 0;

    /**
     * @var array
     */
    private $likes = [];

    /**
     * @var CarbonRange
     */
    public $timeRange;

    public function __construct()
    {
        $this->timeRange = new CarbonRange();
    }

    public function addLike(Like $like): void
    {
        $this->likes[] = $like;
        $this->timeRange->addDate($like->time);
    }

    public function jsonSerialize()
    {
        return [
            'earliest' => $this->timeRange->atomEarliest(),
            'latest'   => $this->timeRange->atomLatest(),
            'likes'    => $this->likes,
        ];
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function current(): Like
    {
        return $this->likes[$this->position];
    }

    public function key(): int
    {
        return $this->position;
    }

    public function next()
    {
        ++$this->position;
    }

    public function valid(): bool
    {
        return isset($this->likes[$this->position]);
    }

    public function count(): int
    {
        return count($this->likes);
    }
}
