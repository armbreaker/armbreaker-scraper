<?php

/*
 * Copyright (c) 2018 Armbreaker Developers.
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Armbreaker;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\ErrorHandler;
use Monolog\Registry;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\GitProcessor;
use Monolog\Formatter\LineFormatter;

/**
 * Description of LogHandler
 *
 * @author Keira Sylae Aro <sylae@calref.net>
 */
class Log
{

    public function __construct()
    {
        if (PHP_SAPI === 'cli') {
            $dest = STDERR;
        } else {
            $dest = "log.txt";
        }
        $l_console  = new StreamHandler($dest, Logger::DEBUG); //@todo: configure option
        $l_console->setFormatter(new LineFormatter(null, null, true, true));
        $l_template = new Logger("Armbreaker");
        $l_template->pushHandler($l_console);
        ErrorHandler::register($l_template);
        // $l_template->pushProcessor(new IntrospectionProcessor());
        // $l_template->pushProcessor(new GitProcessor());
        Registry::addLogger($l_template);
        ErrorHandler::register(Registry::getInstance("Armbreaker"));
    }

    public static function l(): Logger
    {
        return Registry::getInstance("Armbreaker");
    }
}
