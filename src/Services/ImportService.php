<?php

namespace IceCatBundle\Services;

use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse;
use Pimcore\Log\ApplicationLogger;
use Symfony\Component\HttpClient\HttpClient;

class ImportService
{
    public $importUrl = 'https://live.icecat.biz/api/';
    private $searchKey;
    private $status = [
        'FETCHING' => 'fetching',
        'DONE' => 'fetching_done',
        'DONE_WITH_ERROR' => 'fetching_done_with_error',
        'PARTIALLY_DONE' => 'fetched_with_error',
    ];
    private $readerArray;
    private $icecatUserName;
    private $language = 'en';

    private $languages = [];
    private $currentJObId;
    const IMPORTED_DATA_CONTAINER_TABLE = 'icecat_imported_data';
    const JOB_DATA_CONTAINER_TABLE = 'ice_cat_processes';
    const REASON = [
        'COULD_NOT_RESOLVE_HOST' => 'SERVER NOT FOUND',
        'INVALID_KEY' => 'NO VALID KEY AVAILABLE',
        'PRODUCT_NOT_FOUND' => 'PRODUCT NOT FOUND',
        'INVALID_LANGUAGE' => 'LANGUAGE NOT FOUND',
        'USER_MISSING' => 'UserName, lang are mandatory fields',
        'BAD_REQUEST' => 'API Token is not valid UUID',
        'FORBIDDEN' => 'Forbidden: Display of content for users with a Full Icecat subscription level will require the use of a server certificate and a dynamic secret phrase. Please, contact your account manager for help with the implementation.',
        'UNHANDLED' => 'Something went wrong. Please check logs.'
    ];

    const STATUS_CODE_REASON_MAP = [
        1 => 'UserName, lang are mandatory fields',
        22 => 'SERVER NOT FOUND',
        4 => 'PRODUCT NOT FOUND',
        2 => 'LANGUAGE NOT FOUND',
        19 => 'API Token is not valid UUID',
        9 => 'Forbidden: Display of content for users with a Full Icecat subscription level will require the use of a server certificate and a dynamic secret phrase. Please, contact your account manager for help with the implementation.',
        23 => 'Something went wrong. Please check logs.'
    ];


    const APPLOGGER_PREFIX = 'ICECAT';
    const PROCESS_TYPE = [
        'OBJECT_CREATION' => 'OBJECT-CREATION',
        'DATA_IMPORT' => 'DATA-IMPORT'
    ];

    const SUBSCRIPTION_LEVELS = [

        1 => "FULL",
        4 => "FULL",
        0 => "NO_SUBSCRIPTION",
        5 => "OPEN",
        6 => "OPEN"

    ];

    const LOGIN_TABLE = 'icecat_user_login';

    protected $gtin = -1;
    protected $brandName = '';
    protected $productCode = '';
    protected $productName = '';
    protected $insertCounter = 0;
    protected $totalLanguageCount = 1;
    protected $jobHandler;


    protected $appLogger;


    protected $skipProductImport = false;

    // This will be stored in GTIN column in database
    // but it is product icecat id , and GTIN Column is storing
    // id in it
    protected $currentProductIceCatId;

    /**
     * Stores App key
     *
     * @var string
     */
    protected $appKey;

    /**
     * Stores API token
     *
     * @var string
     */
    protected $apiToken;


    /**
     * Stores subscription level
     *
     * @var string
     */
    protected $subscriptionLevel;


    /**
     *
     * @var string
     */
    protected $contentToken;


    /**
     * @var mixed
     */
    protected $jobObject;

    /**
     *
     * @var mixed
     */
    protected $logger;


    /**
     * @var string
     */
    protected $logMessage;


    /**
     * @var string
     */
    protected $pimcoreUserId;


    /**
     * Total number of that is fetced from icecat and
     * need to be processed
     *
     * @var string
     */
    protected $totalFetchedRecords;

    public function __construct(JobHandlerService $jobObject, IceCatLogger $logger, JobHandlerService $jobHandler, ApplicationLogger $appLogger)
    {
        $this->jobObject = $jobObject;
        $this->logger = $logger;
        $this->jobHandler = $jobHandler;
        $this->appLogger = $appLogger;
    }

    public function updateCurrentJob($updateArray, $identifierCol, $identifierVal)
    {
        $db = \Pimcore\Db::get();
        $updateCols = '';
        foreach ($updateArray as $key => $value) {
            $updateCols .= " $key  =   '$value' ,";
        }
        $updateCols = rtrim($updateCols, ',');
        $updateQuery = 'UPDATE ' . self::JOB_DATA_CONTAINER_TABLE . " SET $updateCols
        WHERE $identifierCol =  '$identifierVal' ";
        $db->exec($updateQuery);

        return true;
    }

    public function getCsvTotalcount($file)
    {
        $counter = 0;
        $blankCounter = 0;
        $loopCounter = 0;
        while (($csvrow = fgetcsv($file, 0, ',')) !== false) {
            if ($loopCounter != 0) {
                if (count(array_filter($csvrow)) < 1) {
                    $blankCounter++;
                }

                $counter++;
            }
            $loopCounter++;
        }

        return ['totalRecords' => $counter, 'blankRecords' => $blankCounter];
    }

    public function getXlsTotalCount($file)
    {
    }

    /***
     * Method: Reads data from file ,and process them
     * it also Set the class proprety arrayReader
     * * @return array $row  Return the data into array
     */
    public function processFileData($fileName, $fileExtension, $pimUserId)
    {
        try {
            // LOGGING
            $this->logMessage = 'PROCESSING FILE FOR JOB ID :' . $this->currentJObId;
            $this->logger->addLog('import', $this->logMessage, '', 'NOTICE');

            // updating job status
            $updateArray = ['status' => 'fetching', 'starting_dateTime' => date('Y-m-d H:i:s')];
            $this->updateCurrentJob($updateArray, 'jobid', $this->currentJObId);

            $filepath = PIMCORE_PRIVATE_VAR . "/ice-cat/$pimUserId/$fileName";
            $filetype = "$fileExtension";
            $file_open = fopen($filepath, 'r');
            if ($filetype == 'csv') {
                $totalCsvCount = $this->getCsvTotalcount($file_open); //  * $this->totalLanguageCount;
                // updating fetching status
                $updateArray = [
                    'fetching_status' => $this->status['FETCHING'], 'last_run_dateTime' => date('Y-m-d H:i:s'), 'total_fetch_records' => $totalCsvCount['totalRecords'] * $this->totalLanguageCount, 'fetched_blank_records' => $totalCsvCount['blankRecords'],
                    'file_row_count' => $totalCsvCount['totalRecords']

                ];

                $this->totalFetchedRecords = $totalCsvCount['totalRecords'];

                $this->updateCurrentJob($updateArray, 'jobid', $this->currentJObId);

                if ($totalCsvCount < 1) {
                    // now row found
                    $result = ['status' => 'error', 'message' => 'No row found'];
                    $updateArray = ['fetching_status' => $this->status['PARTIALLY_DONE'], 'fetching_error' => 'No row found', 'last_run_dateTime' => date('Y-m-d H:i:s')];
                    $this->updateCurrentJob($updateArray, 'jobid', $this->currentJObId);

                    return $result;
                }

                $file_open = fopen($filepath, 'r');
                $headerArray = fgetcsv($file_open, 0, ',');
                $this->readerArray = array_change_key_case(array_flip(array_map('trim', $headerArray)), CASE_UPPER);
                $currentCsvCount = 1;
                while (($csvrow = fgetcsv($file_open, 0, ',')) !== false) {
                    if (count(array_filter($csvrow)) >= 1) {
                        foreach ($this->languages as $language) {
                            $isJobAlive = $this->jobHandler->isLive($this->currentJObId);
                            if ($isJobAlive === false) {
                                $this->logMessage = 'JOB TERMINATED FROM FRONTEND:' . $this->currentJObId;
                                $this->logger->addLog('import', $this->logMessage, '', 'INFO');

                                return true;
                            }
                            $this->language = $language;
                            $result = $this->processRowData($csvrow, $fileName, $currentCsvCount);
                            $currentCsvCount++;
                        }
                    }
                }
            } else {
                $spreadsheet = IOFactory::load($filepath);
                $sheets = $spreadsheet->getActiveSheet();
                $maxCell = $sheets->getHighestRowAndColumn();
                $excelDataArray = $sheets->rangeToArray('A1:' . $maxCell['column'] . $maxCell['row']);

                $headerArray = array_filter($excelDataArray[0]);
                $this->readerArray = array_change_key_case(array_flip(array_map('trim', $headerArray)), CASE_UPPER);
                unset($excelDataArray[0]);
                $currentXlsCount = 1;
                $totalXlsCount = count($excelDataArray); // * $this->totalLanguageCount;;

                $updateArray = [
                    'status' => $this->status['FETCHING'], 'starting_dateTime' => date('Y-m-d H:i:s'), 'total_fetch_records' => $totalXlsCount * $this->totalLanguageCount,
                    'file_row_count' => $totalXlsCount
                ];
                $this->updateCurrentJob($updateArray, 'jobid', $this->currentJObId);

                $this->totalFetchedRecords = $totalXlsCount;

                foreach ($excelDataArray as $value) {
                    // $rows[] =  $value;
                    foreach ($this->languages as $language) {
                        $isJobAlive = $this->jobHandler->isLive($this->currentJObId);
                        if ($isJobAlive === false) {
                            $this->logMessage = 'JOB TERMINATED FROM FRONTEND:' . $this->currentJObId;
                            $this->logger->addLog('import', $this->logMessage, '', 'INFO');

                            return true;
                        }

                        $this->language = $language;
                        $result = $this->processRowData($value, $fileName, $currentXlsCount);
                        $currentXlsCount++;
                    }
                }
            }

            $ended_at = date('Y-m-d H:i:s');
            $result = ['status' => 'success', 'message' => 'Product Imported'];

            return $result;
        } catch (\Exception $e) {
            // LOGGING
            $this->logMessage = 'ERROR IN  PROCESSING ROW FOR JOB ID :' .  $this->currentJObId;
            $this->logger->addLog('import', $this->logMessage, [addslashes($e->getMessage()), $e->getTraceAsString()], 'ERROR');

            $updateArray = ['fetching_status' => $this->status['DONE_WITH_ERROR'], 'fetching_error' => addslashes($e->getMessage()), 'last_run_dateTime' => date('Y-m-d H:i:s')];
            $this->updateCurrentJob($updateArray, 'jobid', $this->currentJObId);
        }
    }

    /**
     * Method : fetchRow data ,And Insert into table
     *
     */
    public function processRowData($singleRow, $filename, $counter)
    {
        $db = \Pimcore\Db::get();
        $this->gtin = -1;
        $this->currentProductIceCatId = '';
        $isProductFound = 0;
        $reason = 0;
        try {
            $url = $this->selectUrl($singleRow, $this->icecatUserName, $this->language);

            if ($url == -1) {
                $reason = self::REASON['INVALID_KEY'];

                $this->gtin = ($this->gtin == -1 || $this->gtin == '') ? 'ROW-' . $counter + 1 : $this->gtin;
                $insertQuery = 'INSERT INTO ' . self::IMPORTED_DATA_CONTAINER_TABLE . "(data_encoded,job_id,is_product_found,original_gtin,gtin
                ,data,pim_user_id,icecat_username,product_name,search_key,base_file, error, duplicate,language,reason) VALUES ('','$this->currentJObId' ,$isProductFound,'$this->gtin','$this->gtin ',
                '',$this->pimcoreUserId,'$this->icecatUserName','','', '$filename', 'URL not found to get product', (duplicate+1) , '$this->language','$reason')
                ON DUPLICATE KEY UPDATE data = VALUES(data) , updated_at = now() , duplicate=(duplicate+1)";
                try {
                    $result = $db->exec($insertQuery);
                    $this->insertCounter++;
                } catch (\Exception $ex) {
                }
                $updateArray = ['fetching_status' => $this->status['PARTIALLY_DONE'], 'fetched_records' => $counter, 'fetching_error' => 'URL not found to get product', 'last_run_dateTime' => date('Y-m-d H:i:s')];
                $this->updateCurrentJob($updateArray, 'jobid', $this->currentJObId);

                // LOGGING
                $this->logMessage = 'STATUS: ' . $reason . 'ROW NUMBER :-' . $counter + 1  . 'FOR JOB ID :' . $this->currentJObId;
                $this->logger->addLog('import', $this->logMessage, '', 'NOTICE');

                return -1;
            }
            //Calling icecat api
            try {
                $response = $this->fetchIceCatData($url, $this->icecatUserName);
                $responseArray = json_decode($response, true);

                # Invalide key error
                if (isset($responseArray['Code']) && $responseArray['Code'] == 400) {
                    $updateArray = ['fetching_status' => $this->status['PARTIALLY_DONE'], 'fetched_records' => $this->totalFetchedRecords, 'fetching_error' => $responseArray['Message'], 'last_run_dateTime' => date('Y-m-d H:i:s')];
                    $this->updateCurrentJob($updateArray, 'jobid', $this->currentJObId);

                    $this->logMessage = 'STATUS: ' . $responseArray['Message'] . ' ROW NUMBER :-' . $counter + 1  . ' FOR JOB ID :' . $this->currentJObId;
                    $this->appLogger->error($this->logMessage, [
                        'component' => self::APPLOGGER_PREFIX . ' ' . self::PROCESS_TYPE['OBJECT_CREATION'],

                    ]);
                    die('Aborting Execution');
                }
            } catch (Exception $e) {
                // IN CASE OF INTERNET ACCESSIBLITY IS NOT AVAILABEL OR ICE CAT'S SERVER IS DOWN
                $response = '';
                $responseArray['COULD_NOT_RESOLVE_HOST'] = true;
            }

            $productName = '';
            if (array_key_exists('statusCode', $responseArray)) {
                if ($responseArray['statusCode'] == 4) {
                    $reason = self::REASON['PRODUCT_NOT_FOUND'];
                    $isProductFound = 0;
                } elseif ($responseArray['statusCode'] == 2) {
                    $reason = self::REASON['INVALID_LANGUAGE'];
                    $isProductFound = 0;
                }
            } elseif (array_key_exists('COULD_NOT_RESOLVE_HOST', $responseArray)) {
                $reason = self::REASON['COULD_NOT_RESOLVE_HOST'];
                $isProductFound = 0;
            } elseif (array_key_exists('msg', $responseArray) && $responseArray['msg'] == 'OK') {
                //Product Found
                $reason = '';
                $isProductFound = 1;
                $this->gtin = $responseArray['data']['GeneralInfo']['GTIN'][0] ?? '';
                $this->currentProductIceCatId = $responseArray['data']['GeneralInfo']['IcecatId'];
                $this->productName = $productName = str_replace("'", "''", $responseArray['data']['GeneralInfo']['ProductName']);
            }

            // Proccessing String
            $processedResponse_temp = str_replace("'", "''", $response);
            $processedResponse = str_replace('\"', '\\\\"', $processedResponse_temp);
            $serializedSearchKey = serialize($this->searchKey);
            $fileName = $filename;
            $encodedResponse = base64_encode($response);

            $this->gtin = ($this->gtin == -1 || $this->gtin == '') ? 'ROW-' . $counter : $this->gtin;
            // if productId is not available then we will store row count in it
            $this->currentProductIceCatId = (empty($this->currentProductIceCatId)) ? 'ROW-' . $counter : $this->currentProductIceCatId;
            $insertQuery = 'INSERT INTO ' . self::IMPORTED_DATA_CONTAINER_TABLE . "(data_encoded,job_id,is_product_found,original_gtin,gtin
            ,data,pim_user_id,icecat_username,product_name,search_key,base_file, duplicate , language ,reason) VALUES ('$encodedResponse','$this->currentJObId' ,$isProductFound,'$this->gtin','$this->currentProductIceCatId',
            '$processedResponse',$this->pimcoreUserId,'$this->icecatUserName','$productName','$serializedSearchKey', '$fileName', (duplicate+1) ,  '$this->language','$reason')
            ON DUPLICATE KEY UPDATE data = VALUES(data) , updated_at = now(), duplicate=(duplicate+1), duplicate=(duplicate+1) ";
            $result = $db->exec($insertQuery);
            $this->insertCounter++;

            $updateArray = ['fetched_records' => $counter];
            $this->updateCurrentJob($updateArray, 'jobid', $this->currentJObId);

            // LOGGING
            $this->logMessage = 'STATUS: ' . $reason . 'ROW NUMBER :-' . $counter + 1  . 'FOR JOB ID :' . $this->currentJObId;
            $this->logger->addLog('import', $this->logMessage, '', 'NOTICE');

            return $result;
        } catch (\Exception $e) {
            $isProductFound = 0;
            // LOGGING
            $this->logMessage = 'ERROR IN  PROCESSING ROW FOR JOB ID :' .  $this->currentJObId;
            $this->logger->addLog('import', $this->logMessage, [addslashes($e->getMessage()), $e->getTraceAsString()], 'ERROR');
            $this->gtin = ($this->gtin == -1 || $this->gtin == '') ? 'ROW-' . $counter : $this->gtin;
            $insertQuery = 'INSERT INTO ' . self::IMPORTED_DATA_CONTAINER_TABLE . "(data_encoded,job_id,is_product_found,original_gtin,gtin
            ,data,pim_user_id,icecat_username,product_name,search_key,base_file, error, duplicate) VALUES ('','$this->currentJObId' ,$isProductFound,'$this->gtin',
            '$this->gtin',
            '',$this->pimcoreUserId,'$this->icecatUserName', '$this->productName' ,'', '$filename', 'URL not found to get product', (duplicate+1))
            ON DUPLICATE KEY UPDATE data = VALUES(data) , updated_at = now() ";
            $this->insertCounter++;

            $result = $db->exec($insertQuery);
            $updateArray = ['fetching_status' => $this->status['PARTIALLY_DONE'], 'fetched_records' => $counter, 'fetching_error' => addslashes($e->getMessage()), 'last_run_dateTime' => date('Y-m-d H:i:s')];
            $this->updateCurrentJob($updateArray, 'jobid', $this->currentJObId);
        }
    }

    public function selectUrl($data, $icecatUserName, $language)
    {
        try {
            if (array_key_exists('GTIN', $this->readerArray)) {
                $gtin = isset($data[$this->readerArray['GTIN']]) ? $data[$this->readerArray['GTIN']] : '';
            }
            if (array_key_exists('EAN', $this->readerArray)) {
                $gtin = isset($data[$this->readerArray['EAN']]) ? $data[$this->readerArray['EAN']] : '';
            }

            if (array_key_exists('PRODUCT CODE', $this->readerArray)) {
                $productCode = $this->productCode = isset($data[$this->readerArray['PRODUCT CODE']]) ? $data[$this->readerArray['PRODUCT CODE']] : '';
            }

            if (array_key_exists('BRAND NAME', $this->readerArray)) {
                $brandName = $this->brandName = isset($data[$this->readerArray['BRAND NAME']]) ? $data[$this->readerArray['BRAND NAME']] : '';
            }

            //Deciding url for importing data
            if (isset($gtin) && (!empty($gtin)) && is_numeric($gtin)) {
                $this->searchKey = ['type' => 0, 'gtin' => $gtin];
                $url = $this->importUrl . "?UserName=$icecatUserName&Language=$language&GTIN=$gtin";
            } elseif ((isset($productCode) && !empty($productCode)) && (isset($brandName) && !empty($brandName))) {
                $this->searchKey = ['type' => 0, 'brandName' => $brandName, 'productCode' => $productCode];
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
     * Mehtod Hits the icecat api depending on the url
     * and returns the reswpective response
     *
     * @param $url
     *
     * @return  string $response the Json Response received from ice-cat
     *
     */
    public function fetchIceCatData($url, $icecatUserName)
    {
        $this->initializeKeys($icecatUserName);
        $subscriptionType =  self::SUBSCRIPTION_LEVELS[$this->subscriptionLevel] ?? null;
        $headers['headers']['api-token'] = $this->apiToken;
        if ($subscriptionType == "FULL") {
            //$headers['headers']['content-token'] = $this->contentToken;
            $url .= '&app_key=' . $this->appKey;
        }

        $url .= '&PlatformName=PimcoreOpensourceAddon&PlatformVersion=V4';
        $httpClient = HttpClient::create();
        $responseObject = $httpClient->request('GET', $url, $headers);
        $response = $responseObject->getContent(false);
        return $response;
    }

    /**
     * @return JsonResponse
     */
    public function importData($jobId)
    {
        try {
            $filesData = $this->jobObject->getJobById($jobId);
            if (!empty($filesData)) {
                foreach ($filesData as $file) {
                    // LOGGING
                    $this->logMessage = 'STARTING OBJECT IMPORT FOR JOB ID :' . $file['jobid'];
                    $this->logger->addLog('import', $this->logMessage, '', 'INFO');

                    $this->icecatUserName = $file['icecat_user_name'];
                    $this->pimcoreUserId = $file['pimcore_user_id'];
                    $this->currentJObId = $file['jobid'];
                    $this->languages = explode('|', $file['languages']);
                    $this->totalLanguageCount = $file['total_languages'];

                    $response = $this->processFileData($file['filename'], $file['file_extension'], $this->pimcoreUserId);

                    // LOGGING
                    $this->logMessage = 'OBJECT IMPORT COMPLETED FOR JOB ID :' . $file['jobid'];
                    $this->logger->addLog('import', $this->logMessage, '', 'INFO');
                }
            } else {
                // LOGGING
                $this->logMessage = 'OBJECT IMPORT: IMPORT QUEUE IS EMPTY ';
                $this->logger->addLog('import', $this->logMessage, '', 'INFO');

                $response = ['status' => 'success', 'message' => 'Import Queue is empty'];
            }

            $updateArray = ['status' => 'fetched', 'last_run_dateTime' => date('Y-m-d H:i:s')];
            $this->updateCurrentJob($updateArray, 'jobid', $this->currentJObId);

            return $response;
        } catch (\Exception $e) {
            // LOGGING
            $this->logMessage = 'ERROR IN  OBJECT IMPORT FOR JOB ID :' .  $this->currentJObId;
            $this->logger->addLog('import', $this->logMessage, $e->getTraceAsString(), 'ERROR');

            $updateArray = ['status' => 'fetched', 'fetching_status' => $this->status['DONE_WITH_ERROR'], 'fetching_error' => addslashes($e->getMessage()), 'last_run_dateTime' => date('Y-m-d H:i:s')];
            $this->updateCurrentJob($updateArray, 'jobid', $this->currentJObId);
            $response = ['status' => 'error', 'message' => 'Something went wrong! Error: ' . $e->getMessage()];

            return new JsonResponse($response);
        }
    }



    /**
     * This function sets all the required key
     *
     *
     * @param string $icecatUserName
     * @return void
     */
    public function initializeKeys($icecatUserName)
    {
        $db = \Pimcore\Db::get();
        $sql = 'SELECT * FROM ' . self::LOGIN_TABLE . ' WHERE  icecat_user_id =' . $db->quote($icecatUserName);
        $result = $db->fetchRow($sql);
        if (!empty($result)) {
            $this->apiToken = $result['access_token'];
            $this->appKey = $result['app_key'];
            $this->contentToken = $result['content_token'];
            $this->subscriptionLevel = $result['subscription_level'];
        }
    }
}
