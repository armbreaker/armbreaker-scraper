<?php

/*
 * Copyright (c) 2018 Armbreaker Developers.
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
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
