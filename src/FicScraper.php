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
 * Description of FicScraper
 *
 * @author sylae and skyyrunner
 */
class FicScraper extends Fic {

  const SB_URL = "https://forums.spacebattles.com/threads/%s/threadmarks.rss?category_id=1";

  private $rss;

  public function __construct(int $id) {
    $this->rss = file_get_contents(sprintf(self::SB_URL, $id));
    parent::__construct($id, str_replace("Spacebattles Forums - ", "", \qp($this->rss, 'channel>title')->text()));
    $this->sync();

    $this->scrapePostInfo();
    $this->updateChapters();
  }

  public function scrapePostInfo() {
    $posts = [];
    \qp($this->rss, 'item')->each(function(int $index, \DOMElement $item) use (&$posts) {
      $matches = [];
      preg_match("/post-(\\d+)/i", \qp($item, 'link')->text(), $matches);
      if (mb_strlen($matches[1]) > 0 && is_numeric($matches[1])) {
        $pid      = $matches[1];
        $title    = \qp($item, 'title')->text();
        $postDate = new \Carbon\Carbon(\qp($item, 'pubDate')->text());
        $posts[]  = [$pid, $title, $postDate];
      }
    });
    foreach ($posts as $post) {
      $this->posts->addPost(PostFactory::createPost($post[0], $this, $post[1], $post[2]));
    }
  }

  public function updateChapters() {
    foreach ($this->posts as $post) {
      $this->updateChapter($post);
    }
  }

  private function updateChapter(Post $post) {
    // TODO
  }

}
