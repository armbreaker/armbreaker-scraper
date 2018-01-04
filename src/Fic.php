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
 * Description of Fic
 *
 * @author sylae and skyyrunner
 */
class Fic implements \JsonSerializable {

  /**
   * @var int
   */
  public $id;

  /**
   * @var string
   */
  public $name;

  /**
   *
   * @var PostCollection
   */
  public $posts;

  /**
   *
   * @var bool
   */
  public $printMode = false;

  public function __construct(int $id, string $name) {
    $this->id    = $id;
    $this->name  = $name;
    $this->posts = new PostCollection();
  }

  public function loadPosts(bool $loadLikes = false) {
    $this->posts = PostFactory::getPostsInFic($this, $loadLikes);
  }

  public function sync() {
    $sql = DatabaseFactory::get()->prepare('INSERT INTO armbreaker_fics (tid, title, lastUpdated) VALUES(?, ?, ?)
         ON DUPLICATE KEY UPDATE title=VALUES(title), lastUpdated=VALUES(lastUpdated);', ['integer', 'string', 'datetime']);
    $sql->bindValue(1, $this->id);
    $sql->bindValue(2, $this->name);
    $sql->bindValue(3, \Carbon\Carbon::now());
    $sql->execute();
  }

  public function jsonSerialize() {
    $r = [
        'id'   => $this->id,
        'name' => $this->name,
    ];
    if ($this->printMode) {
      $r['users'] = [];
    }
    if ($this->posts instanceof PostCollection) {
      $r['posts'] = $this->posts;
      if ($this->printMode) {
        foreach ($this->posts as $post) {
          foreach ($post->likes as $like) {
            $r['users'][$like->user->id] = $like->user->name;
          }
        }
      }
    }
    return $r;
  }

  public function setPrintMode(bool $set) {
    $this->printMode = $set;
    foreach ($this->posts as $post) {
      $post->setPrintMode($set);
    }
  }

}
