<?php
/**
 * Copyright (c) 2019 Armbreaker Developers.
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Armbreaker\Model;


use Armbreaker\Storage;
use Carbon\CarbonRange;

class ChapterStorage extends Storage
{
    /**
     * @var CarbonRange
     */
    protected $timeRange;
    /**
     * @var CarbonRange
     */
    protected $timeRangeChapters;
    /**
     * @var CarbonRange
     */
    protected $timeRangeLikes;
    /**
     * @var Fic
     */
    protected $fic;

    public function __construct(
        Fic $fic,
        array $data = null
    ) {
        $this->timeRange = new CarbonRange();
        $this->timeRangeChapters = new CarbonRange();
        $this->timeRangeLikes = new CarbonRange();
        $this->fic = $fic;
        parent::__construct($data);
    }

    public function get(int $key): ?Chapter
    {
        return parent::get($key);
    }

    public function set(int $key, Chapter $value)
    {
        parent::set($key, $value);
        $this->timeRangeChapters->addDate($value->time);
        $this->timeRangeLikes->addRange($value->likes->timeRange);
        $this->timeRange->addRange($this->timeRangeChapters);
        $this->timeRange->addRange($this->timeRangeLikes);
        return $this;
    }

    public function jsonSerialize()
    {
        return [
            'earliest' => $this->timeRange->atomEarliest(),
            'latest' => $this->timeRange->atomLatest(),
            'rangeLikes' => [
                'earliest' => $this->timeRangeLikes->atomEarliest(),
                'latest' => $this->timeRangeLikes->atomLatest(),
            ],
            'rangePosts' => [
                'earliest' => $this->timeRangeChapters->atomEarliest(),
                'latest' => $this->timeRangeChapters->atomLatest(),
            ],
            'posts' => $this->all(),
        ];
    }
}
