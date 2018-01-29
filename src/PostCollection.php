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
 * Description of PostCollection
 *
 * @author sylae and skyyrunner
 */
class PostCollection implements \Iterator, \Countable, \JsonSerializable {

  /**
   * @var int
   */
  private $position = 0;

  /**
   * @var array
   */
  private $posts = [];

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

  /**
   *
   * @var \Carbon\Carbon
   */
  public $earliestCh;

  /**
   *
   * @var \Carbon\Carbon
   */
  public $latestCh;

  /**
   *
   * @var \Carbon\Carbon
   */
  public $earliestLi;

  /**
   *
   * @var \Carbon\Carbon
   */
  public $latestLi;

  public function addPost(Post $post): void {
    $this->posts[] = $post;
    $this->setRange();
  }

  private function setRange() {
    if (count($this->posts) == 1) {
      $this->earliest = clone $this->posts[0]->time;
      $this->latest   = clone $this->posts[0]->time;
      return;
    }
    foreach ($this->posts as $post) {
      $this->earliestCh = clone $post->time->min($this->earliestCh);
      $this->latestCh   = clone $post->time->max($this->latestCh);
      $this->earliestLi = clone $post->likes->earliest->min($this->earliestLi);
      $this->latestLi   = clone $post->likes->latest->max($this->latestLi);
    }
    $this->earliest = clone $this->earliestCh->min($this->earliestLi);
    $this->latest   = clone $this->latestCh->max($this->latestLi);
  }

  public function jsonSerialize() {
    $earliest = null;
    $latest   = null;
    if ($this->earliest instanceof \Carbon\Carbon && $this->latest instanceof \Carbon\Carbon) {
      $earliest = $this->earliest->toAtomString();
      $latest   = $this->latest->toAtomString();
    }
    $chRange = ['earliest' => null, 'latest' => null];
    if ($this->earliestCh instanceof \Carbon\Carbon && $this->latestCh instanceof \Carbon\Carbon) {
      $chRange['earliest'] = $this->earliestCh->toAtomString();
      $chRange['latest']   = $this->latestCh->toAtomString();
    }
    $liRange = ['earliest' => null, 'latest' => null];
    if ($this->earliestLi instanceof \Carbon\Carbon && $this->latestLi instanceof \Carbon\Carbon) {
      $liRange['earliest'] = $this->earliestLi->toAtomString();
      $liRange['latest']   = $this->latestLi->toAtomString();
    }

    return [
        'earliest'   => $earliest,
        'latest'     => $latest,
        'rangeLikes' => $liRange,
        'rangePosts' => $chRange,
        'posts'      => $this->posts,
    ];
  }

  public function rewind() {
    $this->position = 0;
  }

  public function current(): Post {
    return $this->posts[$this->position];
  }

  public function key(): int {
    return $this->position;
  }

  public function next() {
    ++$this->position;
  }

  public function valid(): bool {
    return isset($this->posts[$this->position]);
  }

  public function count(): int {
    return count($this->posts);
  }

}
