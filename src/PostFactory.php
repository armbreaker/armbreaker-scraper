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
        $sql   = DatabaseFactory::get()->prepare("select * from armbreaker_posts where tid=? order by postTime asc");
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
