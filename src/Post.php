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
 * Description of Post
 *
 * @author sylae and skyyrunner
 */
class Post implements \JsonSerializable {

  /**
   *
   * @var int
   */
  public $id;

  /**
   *
   * @var Fic
   */
  public $fic;

  /**
   *
   * @var string
   */
  public $title;

  /**
   *
   * @var LikeCollection
   */
  public $likes;

  /**
   * @var \Carbon\Carbon
   */
  public $time;

  public function __construct(int $pid, Fic $fic, string $title, \Carbon\Carbon $time) {
    $this->id    = $pid;
    $this->fic   = $fic;
    $this->title = $title;
    $this->time  = $time;
    $this->likes = new LikeCollection();
  }

  public function sync() {
    $sql = DatabaseFactory::get()->prepare('INSERT INTO armbreaker_posts (pid, tid, title, postTime, lastUpdated) VALUES(?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE postTime=VALUES(postTime), title=VALUES(title), tid=VALUES(tid), lastUpdated=VALUES(lastUpdated);', ['integer', 'integer', 'string', 'datetime', 'datetime']);
    $sql->bindValue(1, $this->id);
    $sql->bindValue(2, $this->fic->id);
    $sql->bindValue(3, $this->title);
    $sql->bindValue(4, $this->time);
    $sql->bindValue(5, \Carbon\Carbon::now());
    $sql->execute();
  }

  public function loadLikes() {
    Log::l()->debug("Loading likes for post id {$this->id}.");
    $this->likes = LikeFactory::getLikesInPost($this);
  }

  public function jsonSerialize() {
    $j = [
        'id'    => $this->id,
        'fic'   => $this->fic->id,
        'title' => $this->title,
    ];
    if (count($this->likes) > 0) {
      $j['likes'] = $this->likes;
    }
    return $j;
  }

}
