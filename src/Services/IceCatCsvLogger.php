<?php

namespace IceCatBundle\Services;

use Throwable;
use IceCatBundle\Services\AbstractService;



class IceCatCsvLogger
{

    protected $log = [];
    protected $path = [];
    protected $counter = 0;
    public  $logFilePath  = PIMCORE_PRIVATE_VAR . '/log';
    public $abstractObj;
    protected $currentFileName;
    const LOG_TYPE = [
        'FILE_IMPORT' => 'FILE_IMPORT',
        'OBJECT_CREATE' => 'OBJECT_CREATE',
    ];



    function __construct(AbstractService $abstract)
    {
        $this->abstractObj = $abstract;
    }


    public function addLogRow($gtin, $status, $message, $fileName)
    {
        try {
            $this->log[$this->counter]['GTIN'] = $gtin;
            $this->log[$this->counter]['STATUS'] = $status;
            $this->log[$this->counter]['MESSAGE'] = implode(' | ', $message);

            $this->counter++;
            $this->currentFileName = $fileName;
            $this->cleanUpLogger();
        } catch (\Throwable $e) {
            p_r($e->getMessage());
        }
    }

    public function setFileName($fileName)
    {
        try {
            $this->currentFileName = $fileName;
        } catch (\Throwable $e) {
        }
    }
    public function cleanUpLogger()
    {

        try {
            if (count($this->log) > 100) {
                $this->saveLog($this->currentFileName, self::LOG_TYPE['OBJECT_CREATE']);
            }
        } catch (\Throwable $e) {
        }
    }

    public function saveLog($fileName, $logType)
    {

        try {

            if ($logType == self::LOG_TYPE['FILE_IMPORT']) {
                $logFilePath = $this->logFilePath . '/FILE-IMPORT';
            } elseif ($logType == self::LOG_TYPE['OBJECT_CREATE']) {
                $logFilePath = $this->logFilePath . '/OBJECT-CREATE';
            }


            if (!$this->abstractObj->checkFilePathExist($logFilePath, true)) {
                echo 'error: unable to create dir';
            }
            $path = $logFilePath . '/' . str_replace(' ', '-', $fileName) . '.csv';

            if (!file_exists($path)) {

                (array_unshift($this->log, ["GTIN", "STATUS", "MESSAGE"]));
            }

            // Saving file in csv
            $fp = fopen("$path", 'a');
            foreach ($this->log as $log) {
                fputcsv($fp, $log);
            }
            fclose($fp);
            $this->log = [];
        } catch (\Throwable $e) {
        }
    }

    public function getLogFilesDetail()
    {
        try {
            $path = $this->logFilePath . '/OBJECT-CREATE';

            $files = glob("$path/*.csv");
            $fileDetail = [];
            $i = 0;
            foreach ($files as $file) {

                $fileDetail[$i]['path']  = $file;
                $fileDetail[$i]['name'] = basename($file, '.csv');

                $i++;
            }
            return array_reverse($fileDetail);
        } catch (\Throwable $e) {
        }
    }
}
