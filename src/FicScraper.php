<?php

/*
 * Copyright (c) 2018 Armbreaker Developers.
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Armbreaker;

use \GuzzleHttp\Client;

/**
 * This is the messy bit. Scrape SB and return info to parse.
 *
 * @author sylae and skyyrunner
 */
class FicScraper extends Fic
{
    const SB       = "https://forums.spacebattles.com";
    const SB_RSS   = "/threads/%s/threadmarks.rss?category_id=1";
    const SB_LIKES = "/posts/%s/likes?page=%s";

    /**
     * Whether or not to introduce delays for reasons (basically pls dont ddos sb
     * by mistake)
     * @var bool
     */
    public $sleppy = true;

    /**
     * Just holding data here dont mind me.
     * @var string
     */
    private $rss;

    /**
     * Our HTTP library
     * @var \GuzzleHttp\Client
     */
    private $http;

    /**
     * Constructor
     * @param int $id SB topic ID to scrape.
     */
    public function __construct(int $id)
    {
        $this->id   = $id;
        $this->http = new Client([
            'base_uri' => self::SB,
            'timeout'  => 30,
            'headers'  => [
                'User-Agent'   => 'sylae/armbreaker (https://github.com/sylae/armbreaker)',
                'X-Armbreaker' => sprintf('entityType %s; hostID %s', ConfigFactory::get()['type'], ConfigFactory::get()['id']),
            ]
        ]);
    }

    /**
     * Get our chapter information from the topic's RSS information.
     */
    public function scrapePostInfo()
    {
        try {
            Log::l()->info("Scraping chapters for ficID {$this->id}");
            $this->rss = $this->get(sprintf(self::SB_RSS, $this->id));
            parent::__construct($this->id, str_replace("Spacebattles Forums - ", "", \qp($this->rss, 'channel>title')->text()));
            $this->sync();
            $posts     = [];
            \qp($this->rss, 'item')->each(function (int $index, \DOMElement $item) use (&$posts) {
                $matches = [];
                preg_match("/post-(\\d+)/i", \qp($item, 'link')->text(), $matches);
                if (array_key_exists(1, $matches) && mb_strlen($matches[1]) > 0 && is_numeric($matches[1])) {
                    $pid      = $matches[1];
                    $title    = \qp($item, 'title')->text();
                    $postDate = new \Carbon\Carbon(\qp($item, 'pubDate')->text());
                    $postDate->setTimezone("UTC");
                    $posts[]  = [$pid, $title, $postDate];
                }
            });
            foreach ($posts as $post) {
                Log::l()->info("Scraping post {$post[0]} - {$post[1]}");
                $this->posts->addPost(PostFactory::createPost($post[0], $this, $post[1], $post[2]));
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $code = $e->getResponse()->getStatusCode();
            $line = $e->getResponse()->getReasonPhrase();
            Log::l()->addError("Guzzle error pulling RSS in scraper", ['resp' => $code . " " . $line]);
        }
    }

    /**
     * foreach wrapper around updateChapter :v
     */
    public function updateChapters()
    {
        foreach ($this->posts as $post) {
            $this->updateChapter($post);
        }
    }

    /**
     * Scrape a particular post's likes and add them to the DB.
     * @param \Armbreaker\Post $post
     */
    public function updateChapter(Post $post)
    {
        $likes      = [];
        $page       = 1;
        $checkAgain = true;
        while ($checkAgain) {
            Log::l()->info("Scraping likes for post {$post->id} ({$post->title}) // Page $page");
            $html = $this->get(sprintf(self::SB_LIKES, $post->id, $page));
            $obj  = \html5qp($html, 'li.memberListItem');
            $obj->each(function (int $index, \DOMElement $item) use (&$likes) {
                $likes[] = ['time' => $this->unfuckDates(\qp($item, '.DateTime')),
                    'user' => ['name' => \qp($item, 'h3.username')->text(),
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

    /**
     * Get data. includes a sleppy by default so we don't ddos SB.
     * @param string $url
     * @param bool $slep
     * @return string
     * @todo error checking
     */
    private function get(string $url, bool $slep = true): string
    {
        if ($slep) {
            $this->slep();
        }
        $r = $this->http->get($url);
        return $r->getBody();
    }

    /**
     * sleep for between 1 and 2.5 seconds.
     * @todo config option
     * @return void
     */
    private function slep(): void
    {
        if ($this->sleppy) {
            usleep(random_int(1000, 2500) * 1000);
        }
    }

    /**
     * Spacebattles sends us dates in...weird formats. Standardize and parse them.
     * @param \QueryPath\DOMQuery $qp
     * @return \Carbon\Carbon
     * @throws \LogicException
     */
    private function unfuckDates(\QueryPath\DOMQuery $qp): \Carbon\Carbon
    {
        if ($qp->is("span")) {
            $obj = new \Carbon\Carbon(str_replace(" at", "", $qp->attr("title")), "America/New_York");
        } elseif ($qp->is("abbr")) {
            $obj = new \Carbon\Carbon(date('c', $qp->attr("data-time")));
        } else {
            throw new \LogicException("what the fuck");
        }
        $obj->setTimezone("UTC");
        return $obj;
    }

    /**
     * Turn a messy username/string into a nice integer
     * @param string $uid
     * @return int
     */
    private function unfuckUserID(string $uid): int
    {
        $matches = [];
        preg_match("/\\.(\\d+)\\//i", $uid, $matches);
        if (mb_strlen($matches[1]) > 0 && is_numeric($matches[1])) {
            return (int) $matches[1];
        }
    }
}
