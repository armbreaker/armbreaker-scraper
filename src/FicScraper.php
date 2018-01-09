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

  const SB_RSS   = "https://forums.spacebattles.com/threads/%s/threadmarks.rss?category_id=1";
  const SB_LIKES = "https://forums.spacebattles.com/posts/%s/likes?page=%s";

  /**
   * Whether or not to introduce delays for reasons
   * @var bool
   */
  public $sleppy = true;

  /**
   *
   * @var string
   */
  private $rss;

  public function __construct(int $id) {
    Log::l()->info("Scraping ficID $id");
    ini_set('user_agent', "sylae/armbreaker (https://github.com/sylae/armbreaker");
    $this->rss = $this->get(sprintf(self::SB_RSS, $id));
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
      if (array_key_exists(1, $matches) && mb_strlen($matches[1]) > 0 && is_numeric($matches[1])) {
        $pid      = $matches[1];
        $title    = \qp($item, 'title')->text();
        $postDate = new \Carbon\Carbon(\qp($item, 'pubDate')->text());
        $posts[]  = [$pid, $title, $postDate];
      }
    });
    foreach ($posts as $post) {
      Log::l()->info("Scraping post {$post[0]} - {$post[1]}");
      $this->posts->addPost(PostFactory::createPost($post[0], $this, $post[1], $post[2]));
    }
  }

  public function updateChapters() {
    foreach ($this->posts as $post) {
      $this->updateChapter($post);
    }
  }

  public function updateChapter(Post $post) {
    $likes      = [];
    $page       = 1;
    $checkAgain = true;
    while ($checkAgain) {
      Log::l()->info("Scraping likes for post {$post->id} // Page $page");
      $html = $this->get(sprintf(self::SB_LIKES, $post->id, $page));
      $obj  = \html5qp($html, 'li.memberListItem');
      $obj->each(function(int $index, \DOMElement $item) use (&$likes) {
        $likes[] = [
            'time' => $this->unfuckDates(\qp($item, '.DateTime')),
            'user' => [
                'name' => \qp($item, 'h3.username')->text(),
                'id'   => \qp($item, 'a.username')->attr("href"),
            ],
        ];
      });
      if (count($obj) < 100) {
        $checkAgain = false;
      }
      $page++;
    }
    foreach ($likes as $like) {
      try {
        $user = UserFactory::createUser($this->unfuckUserID($like['user']['id']), $like['user']['name']);
        $post->likes->addLike(LikeFactory::createLike($user, $post, $like['time']));
        Log::l()->info("Adding like for {$post->id} - {$user->name}");
      } catch (\Throwable $e) {
        var_dump($like);
        echo $e->xdebug_message;
        die();
      }
    }
  }

  private function get(string $url): string {
    $this->slep();
    return file_get_contents($url);
  }

  private function slep(): void {
    if ($this->sleppy) {
      usleep(random_int(1000, 2500) * 1000);
    }
  }

  private function unfuckDates(\QueryPath\DOMQuery $qp): \Carbon\Carbon {
    if ($qp->is("span")) {
      $obj = new \Carbon\Carbon(str_replace(" at", "", $qp->attr("title")), "America/New_York");
    } elseif ($qp->is("abbr")) {
      $obj = new \Carbon\Carbon(date('c', $qp->attr("data-time")));
    } else {
      throw new \LogicException("what the fuck");
    }
    return $obj;
  }

  private function unfuckUserID(string $uid): int {
    $matches = [];
    preg_match("/\\.(\\d+)\\//i", $uid, $matches);
    if (mb_strlen($matches[1]) > 0 && is_numeric($matches[1])) {
      return (int) $matches[1];
    }
  }

}
