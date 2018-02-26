<?php

/*
 * Copyright (c) 2018 Armbreaker Developers.
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Armbreaker;

/**
 * Holds information on a user
 *
 * @author sylae and skyyrunner
 */
class User implements \JsonSerializable
{

    /**
     * @var int
     */
    public $id;

    /**
     *
     * @var bool
     */
    public $printMode = false;

    /**
     * @var string
     */
    public $name;

    public function __construct(int $id, string $name)
    {
        $this->id   = $id;
        $this->name = $name;
    }

    public function sync()
    {
        $sql = DatabaseFactory::get()->prepare('INSERT INTO armbreaker_users (uid, username, lastUpdated) VALUES(?, ?, ?)
         ON DUPLICATE KEY UPDATE username=VALUES(username), lastUpdated=VALUES(lastUpdated);', ['integer', 'string', 'datetime']);
        $sql->bindValue(1, $this->id);
        $sql->bindValue(2, $this->name);
        $sql->bindValue(3, \Carbon\Carbon::now());
        $sql->execute();
    }

    public function jsonSerialize()
    {
        if ($this->printMode) {
            return $this->id;
        } else {
            return [
                'id'   => $this->id,
                'name' => $this->name,
            ];
        }
    }

    public function setPrintMode(bool $set)
    {
        $this->printMode = $set;
    }
}
