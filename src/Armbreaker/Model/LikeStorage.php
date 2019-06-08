<?php
/**
 * Copyright (c) 2019 Armbreaker Developers.
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Armbreaker\Model;


use Armbreaker\Storage;
use Carbon\CarbonRange;

class LikeStorage extends Storage
{
    /**
     * @var CarbonRange
     */
    protected $timeRange;
    /**
     * @var Chapter
     */
    protected $chapter;

    public function __construct(
        Chapter $chapter,
        array $data = null
    ) {
        $this->timeRange = new CarbonRange();
        $this->chapter = $chapter;
        parent::__construct($data);
    }

    public function get(int $key): ?Like
    {
        return parent::get($key);
    }

    public function set(int $key, Like $value)
    {
        parent::set($key, $value);
        $this->timeRange->addDate($value->time);
        return $this;
    }

    public function jsonSerialize()
    {
        return [
            'earliest' => $this->timeRange->atomEarliest(),
            'latest' => $this->timeRange->atomLatest(),
            'likes' => $this->all(),
        ];
    }
}
