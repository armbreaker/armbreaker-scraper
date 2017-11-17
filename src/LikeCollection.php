<?php

/*
 * The MIT License
 *
 * Copyright 2017 sylae and skyyrunner.
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
 * Description of LikeCollection
 *
 * @author sylae and skyyrunner
 */
class LikeCollection implements \Iterator, \Countable, \JsonSerializable {

  /**
   * @var int
   */
  private $position = 0;

  /**
   * @var array
   */
  private $likes = [];

  /**
   *
   * @var \Carbon\Carbon
   */
  public $earliest;

  /**
   *
   * @var \Carbon\Carbon
   */
  public $latest;

  public function addLike(Like $like): void {
    $this->likes[] = $like;
    $this->setRange();
  }

  private function setRange() {
    if (count($this->likes) == 1) {
      $this->earliest = clone $this->likes[0]->time;
      $this->latest   = clone $this->likes[0]->time;
      return;
    }
    foreach ($this->likes as $like) {
      $this->earliest = clone $like->time->min($this->earliest);
      $this->latest   = clone $like->time->max($this->latest);
    }
  }

  public function jsonSerialize() {
    $earliest = null;
    $latest   = null;
    if ($this->earliest instanceof \Carbon\Carbon && $this->latest instanceof \Carbon\Carbon) {
      $earliest = $this->earliest->toAtomString();
      $latest   = $this->latest->toAtomString();
    }

    return [
        'earliest' => $earliest,
        'latest'   => $latest,
        'likes'    => $this->likes,
    ];
  }

  public function rewind() {
    $this->position = 0;
  }

  public function current(): Like {
    return $this->likes[$this->position];
  }

  public function key(): int {
    return $this->position;
  }

  public function next() {
    ++$this->position;
  }

  public function valid(): bool {
    return isset($this->likes[$this->position]);
  }

  public function count(): int {
    return count($this->likes);
  }

}
