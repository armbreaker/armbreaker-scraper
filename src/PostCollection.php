<?php

/*
 * Copyright (c) 2018 Armbreaker Developers.
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Armbreaker;

/**
 * Description of PostCollection
 *
 * @author sylae and skyyrunner
 */
class PostCollection implements \Iterator, \Countable, \JsonSerializable
{

    /**
     * @var int
     */
    private $position = 0;

    /**
     * @var array
     */
    private $posts = [];

    /**
     * @var CarbonRange
     */
    public $timeRange;

    /**
     * @var CarbonRange
     */
    public $timeRangeChapters;

    /**
     * @var CarbonRange
     */
    public $timeRangeLikes;

    public function __construct()
    {
        $this->timeRange         = new CarbonRange();
        $this->timeRangeChapters = new CarbonRange();
        $this->timeRangeLikes    = new CarbonRange();
    }

    public function addPost(Post $post): void
    {
        $this->posts[] = $post;
        $this->timeRangeChapters->addDate($post->time);
        $this->timeRangeLikes->addRange($post->likes->timeRange);
        $this->timeRange->addRange($this->timeRangeChapters);
        $this->timeRange->addRange($this->timeRangeLikes);
    }

    public function jsonSerialize()
    {
        return [
            'earliest'   => $this->timeRange->atomEarliest(),
            'latest'     => $this->timeRange->atomLatest(),
            'rangeLikes' => [
                'earliest' => $this->timeRangeLikes->atomEarliest(),
                'latest'   => $this->timeRangeLikes->atomLatest(),
            ],
            'rangePosts' => [
                'earliest' => $this->timeRangeChapters->atomEarliest(),
                'latest'   => $this->timeRangeChapters->atomLatest(),
            ],
            'posts'      => $this->posts,
        ];
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function current(): Post
    {
        return $this->posts[$this->position];
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
        return isset($this->posts[$this->position]);
    }

    public function count(): int
    {
        return count($this->posts);
    }
}
