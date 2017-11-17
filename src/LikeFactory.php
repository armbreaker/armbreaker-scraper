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
 * Create a like object
 *
 * @author sylae and skyyrunner
 */
class LikeFactory {

  public static function getLike(User $user, Post $post): Like {
    $sql  = DatabaseFactory::get()->prepare("select * from armbreaker_likes where uid=? and pid=?");
    $sql->bindValue(1, $user->id, 'integer');
    $sql->bindValue(2, $post->id, 'integer');
    $sql->execute();
    $like = $sql->fetchAll();
    if (count($like) == 1) {
      return new Like($user, $post, new \Carbon\Carbon($user[0]['likeTime']));
    } elseif (count($like) == 0) {
      throw new \Exception("Like not found :(");
    } else {
      throw new \LogicException("User liked post more than once?");
    }
  }

  public static function createLike(User $user, Post $post, \Carbon\Carbon $likeTime): Like {
    $like = new Like($user, $post, $post->fic, $likeTime);
    $like->sync();
    return $like;
  }

  public static function getLikesInPost(Post $post): LikeCollection {
    $sql   = DatabaseFactory::get()->prepare("select * from armbreaker_likes where pid=? order by likeTime asc");
    $sql->bindValue(1, $post->id, 'integer');
    $sql->execute();
    $likes = new LikeCollection();
    foreach ($sql->fetchAll() as $like) {
      $likes->addLike(new Like(UserFactory::getUser($like['uid']), $post, new \Carbon\Carbon($like['likeTime'])));
    }
    return $likes;
  }

}
