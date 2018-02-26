<?php

/*
 * Copyright (c) 2018 Armbreaker Developers.
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Armbreaker;

/**
 * Create a user object
 *
 * @author sylae and skyyrunner
 */
class UserFactory
{

    public static function getUser(int $id): User
    {
        static $cache = [];
        if (array_key_exists($id, $cache)) {
            return $cache[$id];
        }
        $sql  = DatabaseFactory::get()->prepare("select * from armbreaker_users where uid= ?");
        $sql->bindValue(1, $id, 'integer');
        $sql->execute();
        $user = $sql->fetchAll();
        if (count($user) == 1) {
            $cache[$user[0]['uid']] = new User($user[0]['uid'], $user[0]['username']);
            return $cache[$user[0]['uid']];
        } elseif (count($user) == 0) {
            throw new \Exception("User ID not found :(");
        } else {
            throw new \LogicException("More than one User ID matched?");
        }
    }

    public static function createUser(int $id, string $name): User
    {
        $user = new User($id, $name);
        $user->sync();
        return $user;
    }
}
