<?php

/*
 * @author Keira Sylae Aro <sylae@calref.net>
 * @copyright Copyright (C) 2017 Keira Sylae Aro <sylae@calref.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
class Log {

  public function __construct() {

    $l_console  = new StreamHandler("log.txt", Logger::DEBUG); //@todo: configure option
    $l_console->setFormatter(new LineFormatter(null, null, true, true));
    $l_template = new Logger("Armbreaker");
    $l_template->pushHandler($l_console);
    ErrorHandler::register($l_template);
    // $l_template->pushProcessor(new IntrospectionProcessor());
    // $l_template->pushProcessor(new GitProcessor());
    Registry::addLogger($l_template);
    ErrorHandler::register(Registry::getInstance("Armbreaker"));
  }

  public static function l(): Logger {
    return Registry::getInstance("Armbreaker");
  }

}
