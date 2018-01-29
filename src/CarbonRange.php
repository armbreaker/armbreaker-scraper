<?php

/*
 * The MIT License
 *
 * Copyright 2018 Keira Sylae Aro <sylae@calref.net>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Armbreaker;

/**
 * A less messy way to get date ranges.
 *
 * @author Keira Sylae Aro <sylae@calref.net>
 */
class CarbonRange {

  /**
   * @var \Carbon\Carbon
   */
  public $earliest = null;

  /**
   * @var \Carbon\Carbon
   */
  public $latest = null;

  /**
   * Parse a new date and recalc the min/max
   * @param \Carbon\Carbon $new
   */
  public function addDate(\Carbon\Carbon $new) {
    if (is_null($this->earliest) || is_null($this->latest)) {
      $this->earliest = clone $new;
      $this->latest   = clone $new;
    } else {
      $this->earliest = clone $new->min($this->earliest);
      $this->latest   = clone $new->max($this->latest);
    }
  }

  public function addRange(CarbonRange $new) {
    if (is_null($this->earliest) || is_null($this->latest)) {
      $this->earliest = clone $new->earliest;
      $this->latest   = clone $new->latest;
    } else {
      $this->earliest = clone $new->earliest->min($this->earliest);
      $this->latest   = clone $new->latest->max($this->latest);
    }
  }

  public function atomEarliest(): ?string {
    if ($this->earliest instanceof \Carbon\Carbon) {
      return $this->earliest->toAtomString();
    }
    return null;
  }

  public function atomLatest(): ?string {
    if ($this->latest instanceof \Carbon\Carbon) {
      return $this->latest->toAtomString();
    }
    return null;
  }

}
