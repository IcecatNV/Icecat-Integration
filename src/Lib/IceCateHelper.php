<?php

namespace IceCatBundle\Lib;

use Carbon\Carbon;
use IceCatBundle\Model\Configuration;
use Pimcore\Db;
use Symfony\Component\HttpClient\HttpClient;

trait IceCateHelper
{

    public $importUrl = 'https://live.icecat.biz/api/';

    /**
     * Generate Icecat url to fetch data
     * @param array $data
     * @param string $icecatUserName
     * @param string $language
     *
     * @return string
     */
    public function getIceCatUrlToGetRecord($data, $icecatUserName, $language)
    {
        try {
            if (array_key_exists('gtin', $data)) {
                $gtin = isset($data['gtin']) ? $data['gtin'] : '';
            }
            if (array_key_exists('ean', $data)) {
                $gtin = isset($data['ean']) ? $data['ean'] : '';
            }

            if (array_key_exists('productCode', $data)) {
                $productCode =isset($data['productCode']) ? $data['productCode'] : '';
            }

            if (array_key_exists('brandName', $data)) {
                $brandName =  isset($data['brandName']) ? $data['brandName'] : '';
            }

            //Deciding url for importing data
            if (isset($gtin) && (!empty($gtin)) && is_numeric($gtin)) {
                $url = $this->importUrl . "?UserName=$icecatUserName&Language=$language&GTIN=$gtin";
            } elseif ((isset($productCode) && !empty($productCode)) && (isset($brandName) && !empty($brandName))) {
                $url = $this->importUrl . "?UserName=$icecatUserName&Language=$language&Brand=$brandName&ProductCode=$productCode";
            } else {
                //Serachable key or combination not found Log some error message for the current row
                $url = -1;
            }

            return $url;
        } catch (\Exception $ex) {
        }
    }

    /**
     * Fetch data from Icecat
     * @param string $url
     *
     * @return string
     */
    public function fetchIceCatData($url)
    {
        $httpClient = HttpClient::create();
        $responseObject = $httpClient->request('GET', $url);
        $response = $responseObject->getContent(false);

        return $response;
    }

    /**
     * Get icecat user to get used in further API calls
     *
     * @return string
     */
    protected function getIcecatLoginUser()
    {
        $db = Db::get();
        $sql = 'SELECT * FROM icecat_user_login WHERE login_status = 1 ORDER BY id DESC';
        $result = $db->fetchRow($sql);
        if (!empty($result)) {
            //return $result['icecat_user_id'];
            return $result;
        } else {
            return null;
        }
    }

    /**
     * @param $dateString
     * @return Carbon|null
     */
    public function getCarbonObjectForDateString($dateString)
    {
        if (empty($dateString)) {
            return null;
        }
        try {
            return Carbon::create($dateString);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check for next run of recurring import
     */
    public function getNextCronJobExecutionTimestamp()
    {
        $conf = Configuration::load();
        if ($conf->getCronExpression()) {
            if (\Pimcore\Tool::classExists('\Cron\CronExpression')) {
                try {
                    $cron = new \Cron\CronExpression($this->getCronJob());
                } catch (\Exception $ex) {
                    return strtotime("02-01-3000");
                }
                $lastExecution = $this->getLastCronJobExecution();
                $lastRunDate = new \DateTime(date('Y-m-d H:i', $lastExecution));
                $nextRun = $cron->getNextRunDate($lastRunDate);
                $nextRunTs = $nextRun->getTimestamp();
                return $nextRunTs;
            } else {
            }
        }
    }

    /**
     * Get recurring last import record
     */
    public function getLastCronJobExecution()
    {
        $db = Db::get();
        $res = $db->fetchRow('Select * from icecat_recurring_import order by id desc limit 1');
        if (!empty($res) && !empty($res['start_datetime'])) {
            return $res['start_datetime'];
        }
        return strtotime("02-01-1970");
    }

    /**
     * Get last recurring import timestamp
     */
    public function getLastCronJobExecutionEndTime()
    {
        $db = Db::get();
        $res = $db->fetchRow('Select * from icecat_recurring_import order by id desc limit 1');
        if (!empty($res) && !empty($res['start_datetime'])) {
            return $res['end_datetime'];
        }
        return strtotime("02-01-1970");
    }

    /**
     * Get cron schedule for recurring import
     */
    public function getCronJob()
    {
        return $this->configuration->getCronExpression();
    }

    /**
     * Check if cron is valid
     */
    public function checkIfCronExpressionValid($cronExpression)
    {
        if ($cronExpression == "") {
            return;
        }
        try {
            $cron = new \Cron\CronExpression($cronExpression);
        } catch (\Exception $ex) {
            throw $ex;
        }
    }
}
