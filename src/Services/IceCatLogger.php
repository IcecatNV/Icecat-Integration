<?php

namespace IceCatBundle\Services;

use Psr\Log\LoggerInterface;

class IceCatLogger
{
    private $importlogger;
    private $objectcreatelogger;
    protected $logMessage;
    protected $logDetail;
    protected $logLevel;
    const LEVEL = ['ERROR' => 'ERROR', 'INFO' => 'INFO', 'NOTICE' => 'NOTICE', 'DEBUG' => 'DEBUG'];

    public function __construct(LoggerInterface $clientdataimportLogger, LoggerInterface $clientobjectcreateLogger)
    {
        $this->importlogger = $clientdataimportLogger;
        $this->objectcreatelogger = $clientobjectcreateLogger;
    }

    /**
     * Generate log files
     *
     * @param string $logType
     * @param string $logMessage
     * @param string $logDetail
     * @param string $logLevel
     *
     * @return void
     */
    public function addLog($logType, $logMessage, $logDetail = '', $logLevel = 'DEBUG')
    {
        $this->logMessage = $logMessage;
        $this->logDetail = $logDetail;
        $this->logLevel = $logLevel;
        if ($logType == 'import') {
            $this->importLog();
        } elseif ($logType == 'create-object') {
            $this->dataCreationLog();
        }
    }

    /**
     * Generate log for import action
     *
     *
     */
    public function importLog()
    {
        $this->importlogger->log($this->logLevel, $this->logMessage, ['detail' => $this->logDetail]);
    }

    /**
     * Generate log for databject creation
     *
     * @return void
     */
    public function dataCreationLog()
    {
        $this->objectcreatelogger->log($this->logLevel, $this->logMessage, ['detail' => $this->logDetail]);
    }
}
