<?php

/*
 * Copyright (c) 2018 Armbreaker Developers.
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Armbreaker;

/**
 * Create a post object
 *
 * @author sylae and skyyrunner
 */
class PostFactory
{
    public static function getPost(int $pid): Post
    {
        Log::l()->debug("Loading post $pid.");
        $sql  = DatabaseFactory::get()->prepare("select * from armbreaker_posts where pid=?");
        $sql->bindValue(1, $pid, 'integer');
        $sql->execute();
        $post = $sql->fetchAll();
        if (count($post) == 1) {
            $fic = new Fic(); // TODO
            return new Post($pid, $fic, $post[0]['title'], new \Carbon\Carbon($post[0]['postTime']));
        } elseif (count($post) == 0) {
            throw new \Exception("Post not found :(");
        } else {
            throw new \LogicException("Post exists multiple times?");
        }
    }

    public static function createPost(int $pid, Fic $fic, string $title, \Carbon\Carbon $postTime): Post
    {
        Log::l()->debug("Creating post $pid for {$fic->id} called $title.");
        $post = new Post($pid, $fic, $title, $postTime);
        $post->sync();
        return $post;
    }

    public static function getPostsInFic(Fic $fic, bool $loadLikes = false): PostCollection
    {
        Log::l()->debug("Loading posts for fic id {$fic->id}, loadLikes is $loadLikes.");
        $sql   = DatabaseFactory::get()->prepare("select * from armbreaker_posts where tid=? order by postTime asc, pid asc");
        $sql->bindValue(1, $fic->id, 'integer');
        $sql->execute();
        $posts = new PostCollection();
        foreach ($sql->fetchAll() as $post) {
            $postObj = new Post($post['pid'], $fic, $post['title'], new \Carbon\Carbon($post['postTime']));
            if ($loadLikes) {
                $postObj->loadLikes();
            }
            $posts->addPost($postObj);
        }
        return $posts;
    }
}
