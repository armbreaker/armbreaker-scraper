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
 * Create a user object
 *
 * @author sylae and skyyrunner
 */
class UserFactory {

  public static function getUser(int $id): User {
    // todo: caching
    $sql  = DatabaseFactory::get()->prepare("select * from armbreaker_users where uid= ?");
    $sql->bindValue(1, $id, 'integer');
    $sql->execute();
    $user = $sql->fetchAll();
    if (count($user) == 1) {
      return new User($user[0]['uid'], $user[0]['username']);
    } elseif (count($user) == 0) {
      throw new \Exception("User ID not found :(");
    } else {
      throw new \LogicException("More than one User ID matched?");
    }
  }

  public static function createUser(int $id, string $name): User {
    $user = new User($id, $name);
    $user->sync();
    return $user;
  }

}
