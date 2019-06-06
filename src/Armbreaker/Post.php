<?php

/*
 * Copyright (c) 2018 Armbreaker Developers.
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Armbreaker;

/**
 * Description of Post
 *
 * @author sylae and skyyrunner
 */
class Post implements \JsonSerializable
{
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
     *
     * @var bool
     */
    public $printMode = false;

    /**
     * @var \Carbon\Carbon
     */
    public $time;

    public function __construct(int $pid, Fic $fic = null, string $title, \Carbon\Carbon $time)
    {
        $this->id    = $pid;
        $this->fic   = $fic;
        $this->title = $title;
        $this->time  = $time;
        $this->likes = new LikeCollection();
    }

    public function sync()
    {
        $sql = DatabaseFactory::get()->prepare('INSERT INTO armbreaker_posts (pid, tid, title, postTime, lastUpdated) VALUES(?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE postTime=VALUES(postTime), title=VALUES(title), tid=VALUES(tid), lastUpdated=VALUES(lastUpdated);', ['integer', 'integer', 'string', 'datetime', 'datetime']);
        $sql->bindValue(1, $this->id);
        $sql->bindValue(2, $this->fic->id);
        $sql->bindValue(3, $this->title);
        $sql->bindValue(4, $this->time);
        $sql->bindValue(5, \Carbon\Carbon::now());
        $sql->execute();
    }

    public function loadLikes()
    {
        Log::l()->debug("Loading likes for post id {$this->id}.");
        $this->likes = LikeFactory::getLikesInPost($this);
    }

    public function jsonSerialize()
    {
        $j = [
            'id'    => $this->id,
            'title' => $this->title,
        ];
        if ($this->time instanceof \Carbon\Carbon) {
            $j['time'] = $this->time->toAtomString();
        } else {
            $j['time'] = null;
        }
        if (count($this->likes) > 0) {
            $j['likes'] = $this->likes;
        }
        if (!$this->printMode) {
            $j['fic'] = $this->fic->id;
        }
        return $j;
    }

    public function setPrintMode(bool $set)
    {
        $this->printMode = $set;
        foreach ($this->likes as $like) {
            $like->setPrintMode($set);
        }
    }
}
