<?php
/**
 * Copyright (c) 2019 Armbreaker Developers.
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Armbreaker;


use Exception;
use InvalidArgumentException;

class EventListener
{

    /**
     *
     * @var array
     */
    private $events = [];

    /**
     *
     * @var int
     */
    private $periodic = 0;

    /**
     *
     * @var callable
     */
    private $callable;

    /**
     * Convenience function to allow easier chaining.
     * @return EventListener
     */
    public static function new(): EventListener
    {
        return new self();
    }

    public function setCallback(callable $call)
    {
        $this->callable = $call;
        return $this;
    }

    /**
     * @return callable
     * @throws Exception
     */
    public function getCallback(): callable
    {
        if (!is_callable($this->callable)) {
            throw new Exception("Callback on EventListener not set!");
        }
        return $this->callable;
    }

    public function getPeriodic(): int
    {
        return $this->periodic;
    }

    public function setPeriodic(int $seconds): EventListener
    {
        if ($seconds < 1) {
            throw new InvalidArgumentException('$seconds must be at least 1.');
        }
        $this->addEvent("periodic");
        $this->periodic = $seconds;
        return $this;
    }

    public function addEvent(string $event): EventListener
    {
        if (!in_array($event, $this->events)) {
            $this->events[] = $event;
        }
        return $this;
    }

    public function match(string $type): bool
    {
        return in_array($type, $this->events);

    }

}
