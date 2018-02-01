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
 * Hold the configuration. Not really a factory, i just suck at naming :v
 *
 * @author sylae and skyyrunner
 */
class ConfigFactory
{

    /**
     * Our actual config goes here
     * @var array
     */
    private static $config = null;

    /**
     * Get a copy of config
     * @return array Copy of the config
     * @throws \Exception
     */
    public static function get(): array
    {
        if (is_null(self::$config)) {
            throw new \Exception("Configuration not loaded! Have you run ConfigFactory::make() yet?");
        }
        return self::$config;
    }

    /**
     * Load the configuration to be served. Best done early on so we can use it :v
     * @param array $config array of config items. Really just what it says on the tin :v
     * @return void
     */
    public static function make(array $config): void
    {
        self::$config = $config;
    }
}
