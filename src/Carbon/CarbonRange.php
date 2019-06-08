<?php

/**
 * Copyright (c) 2019 Armbreaker Developers.
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Carbon;

/**
 * A less messy way to get date ranges.
 *
 * @author Keira Dueck <sylae@calref.net>
 */
class CarbonRange
{

    /**
     * @var CarbonInterface
     */
    public $earliest = null;

    /**
     * @var CarbonInterface
     */
    public $latest = null;

    /**
     * Parse a new date and recalc the min/max
     *
     * @param CarbonInterface $new
     */
    public function addDate(CarbonInterface $new)
    {
        if (is_null($this->earliest) || is_null($this->latest)) {
            $this->earliest = clone $new;
            $this->latest = clone $new;
        } else {
            $this->earliest = clone $new->min($this->earliest);
            $this->latest = clone $new->max($this->latest);
        }
    }

    public function addRange(CarbonRange $new)
    {
        if (is_null($new->earliest) || is_null($new->latest)) {
            return;
        }
        if (is_null($this->earliest) || is_null($this->latest)) {
            $this->earliest = clone $new->earliest;
            $this->latest = clone $new->latest;
        } else {
            $this->earliest = clone $new->earliest->min($this->earliest);
            $this->latest = clone $new->latest->max($this->latest);
        }
    }

    public function atomEarliest(): ?string
    {
        if ($this->earliest instanceof CarbonInterface) {
            return $this->earliest->toAtomString();
        }
        return null;
    }

    public function atomLatest(): ?string
    {
        if ($this->latest instanceof CarbonInterface) {
            return $this->latest->toAtomString();
        }
        return null;
    }
}
