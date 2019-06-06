<?php

/*
 * Copyright (c) 2018 Armbreaker Developers.
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Armbreaker;

/**
 * Create a like object
 *
 * @author sylae and skyyrunner
 */
class LikeFactory
{
    public static function getLike(User $user, Post $post): Like
    {
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

    public static function createLike(User $user, Post $post, \Carbon\Carbon $likeTime): Like
    {
        $like = new Like($user, $post, $likeTime);
        $like->sync();
        return $like;
    }

    public static function getLikesInPost(Post $post): LikeCollection
    {
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
