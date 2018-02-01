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
 * This is the basis of most of our API stuff. Holds an entire fic's resources.
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
   * @var PostCollection
   */
  public $posts;

  /**
   * @var bool
   */
  public $printMode = false;

  /**
   * Create a new fic
   * @param int $id SB topic ID
   * @param string $name Fic title
   */
  public function __construct(int $id, string $name) {
    $this->id    = $id;
    $this->name  = $name;
    $this->posts = new PostCollection();
  }

  /**
   * Pull posts from the database, if we have any.
   * @param bool $loadLikes If true, tell the posts to load likes as well.
   * @return void
   */
  public function loadPosts(bool $loadLikes = false): void {
    $this->posts = PostFactory::getPostsInFic($this, $loadLikes);
  }

  /**
   * Sync with the DB
   * @return void
   */
  public function sync(): void {
    $sql = DatabaseFactory::get()->prepare('INSERT INTO armbreaker_fics (tid, title, lastUpdated) VALUES(?, ?, ?)
         ON DUPLICATE KEY UPDATE title=VALUES(title), lastUpdated=VALUES(lastUpdated);', ['integer', 'string', 'datetime']);
    $sql->bindValue(1, $this->id);
    $sql->bindValue(2, $this->name);
    $sql->bindValue(3, \Carbon\Carbon::now());
    $sql->execute();
  }

  /**
   * Turn shit into JSON. This is how we do most of our API sending :v
   * @return array
   */
  public function jsonSerialize(): array {
    $r = [
        'id'   => $this->id,
        'name' => $this->name,
    ];
    if ($this->printMode) {
      $r['users'] = [];
    }
    if ($this->posts instanceof PostCollection && count($this->posts) > 0) {
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

  /**
   * If true, this will compact things a bit on the json output, such as not
   * putting the username to id relation 800 times.
   * @param bool $set
   * @return void
   */
  public function setPrintMode(bool $set): void {
    $this->printMode = $set;
    foreach ($this->posts as $post) {
      $post->setPrintMode($set);
    }
  }

}
