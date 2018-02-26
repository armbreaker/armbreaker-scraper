<?php

/*
 * Copyright (c) 2018 Armbreaker Developers.
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Armbreaker;

/**
 * Simple wrapper to hold multiple fics. Eventually maybe sorting and stuff?
 *
 * @author sylae and skyyrunner
 */
class FicCollection implements \Iterator, \Countable, \JsonSerializable
{

    /**
     * @var int
     */
    private $position = 0;

    /**
     * @var array
     */
    private $fics = [];

    public function addFic(Fic $fic): void
    {
        $this->fics[] = $fic;
    }

    public function jsonSerialize()
    {
        return $this->fics;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function current(): Fic
    {
        return $this->fics[$this->position];
    }

    public function key(): int
    {
        return $this->position;
    }

    public function next()
    {
        ++$this->position;
    }

    public function valid(): bool
    {
        return isset($this->fics[$this->position]);
    }

    public function count(): int
    {
        return count($this->fics);
    }
}
