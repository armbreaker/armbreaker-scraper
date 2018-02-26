<?php

/*
 * Copyright (c) 2018 Armbreaker Developers.
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Armbreaker;

/**
 * Holds a like
 *
 * @author sylae and skyyrunner
 */
class Like implements \JsonSerializable
{

    /**
     * @var User
     */
    public $user;

    /**
     * @var Post
     */
    public $post;

    /**
     * @var bool
     */
    public $printMode = false;

    /**
     * @var \Carbon\Carbon
     */
    public $time;

    public function __construct(User $user, Post $post, \Carbon\Carbon $time)
    {
        $this->user = $user;
        $this->post = $post;
        $this->time = $time;
    }

    public function sync()
    {
        $sql = DatabaseFactory::get()->prepare('INSERT INTO armbreaker_likes (pid, uid, likeTime, lastUpdated) VALUES(?, ?, FROM_UNIXTIME(?), ?)
         ON DUPLICATE KEY UPDATE likeTime=VALUES(likeTime), lastUpdated=VALUES(lastUpdated);', ['integer', 'string', 'integer', 'datetime']);
        $sql->bindValue(1, $this->post->id);
        $sql->bindValue(2, $this->user->id);
        $sql->bindValue(3, $this->time->timestamp);
        $sql->bindValue(4, \Carbon\Carbon::now());
        $sql->execute();
    }

    public function jsonSerialize()
    {
        $j = [
            'user' => $this->user,
        ];
        if ($this->time instanceof \Carbon\Carbon) {
            $j['time'] = $this->time->toAtomString();
        } else {
            $j['time'] = null;
        }
        if (!$this->printMode) {
            $j['post'] = $this->post->id;
        }
        return $j;
    }

    public function setPrintMode(bool $set)
    {
        $this->printMode = $set;
        $this->user->setPrintMode($set);
    }
}
