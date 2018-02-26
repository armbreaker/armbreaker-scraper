<?php

/*
 * Copyright (c) 2018 Armbreaker Developers.
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Armbreaker;

/**
 * Hold the database
 *
 * @author sylae and skyyrunner
 */
class DatabaseFactory
{

    /**
     * Our DB object. Sacred is thy name.
     * @var \Doctrine\DBAL\Connection
     */
    private static $db = null;

    /**
     * Get a reference to the db object. :snug:
     * @return \Doctrine\DBAL\Connection
     * @throws \Exception
     */
    public static function get(): \Doctrine\DBAL\Connection
    {
        if (is_null(self::$db)) {
            throw new \Exception("Database not set up! Have you run DatabaseFactory::make() yet?");
        }
        return self::$db;
    }

    /**
     * Initialize the database. Make sure config is set beforehand or it'll
     * throw shit.
     * @return void
     */
    public static function make(): void
    {
        self::$db = \Doctrine\DBAL\DriverManager::getConnection(['url' => ConfigFactory::get()['database']], new \Doctrine\DBAL\Configuration());
    }
}
