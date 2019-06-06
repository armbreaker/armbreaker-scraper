<?php
/**
 * Copyright (c) 2019 Armbreaker Developers.
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Armbreaker;


use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\DriverManager;
use Monolog\ErrorHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

trait ArmbreakerBaseTrait
{

    /**
     * @var Connection
     */
    protected $db;

    /**
     * @var QueueInterface
     */
    protected $queue;

    /**
     * Version of armbreaker code.
     * @var string
     */
    protected $version;

    /**
     * @var LoggerInterface
     */
    protected $log;

    /**
     * @return LoggerInterface
     */
    public function l(): LoggerInterface
    {
        return $this->log;
    }

    /**
     * @return Connection
     */
    public function db(): Connection
    {
        return $this->db;
    }

    protected function setupBaseTrait(array $config)
    {
        exec("git diff --quiet HEAD", $null, $rv);
        $this->version = trim(`git rev-parse HEAD`) . ($rv == 1 ? "-modified" : "");

        $this->log = $this->setupLogger();
        $this->log->debug("Logging initialized");
        $this->log->debug("Armbreaker version {$this->version}");

        try {
            $this->db = DriverManager::getConnection(['url' => $config['database']], new Configuration());
            $this->log->debug("DB initialized");
            $this->db->connect();
            $this->log->debug("DB connected");
        } catch (DBALException $e) {
            $this->log->error("Failed to initialize database!", ['exception' => $e]);
            die();
        }

        // todo: config setting
        $this->queue = new MySQLQueue($this->db, $config);
    }

    private function setupLogger(): LoggerInterface
    {
        $l_console = new StreamHandler(STDERR, LOG_DEBUG);
        $l_console->setFormatter(new LineFormatter(null, null, true, true));
        $l_template = new Logger("Armbreaker");
        $l_template->pushHandler($l_console);
        ErrorHandler::register($l_template);
        return $l_template;
    }

}
