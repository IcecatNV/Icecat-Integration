<?php

namespace IceCatBundle\Command;

use IceCatBundle\Services\CreateObjectService;
use IceCatBundle\Services\ImportService;
use IceCatBundle\Model\Configuration;
use Pimcore\Console\AbstractCommand;
use Pimcore\Model\Asset;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Pimcore\Log\Simple;
use Pimcore\Db\ConnectionInterface;
use Pimcore\Db;
use PhpOffice\PhpSpreadsheet\IOFactory;

class RecurringImportCommand extends AbstractCommand
{

    /**
     * @var ConnectionInterface
     */
    protected $db;

    /**
     * @var ImportService
     */
    protected $importService;

    /**
     * @var CreateObjectService
     */
    protected $createObjectService;

    /**
     * @const string
     */
    protected CONST LOG_FILENAME = "icecat_last_import";

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var string
     */
    protected $icecatLoginUser;

    /**
     * @var int
     */
    protected $totalRecords = 0;

    /**
     * @var int
     */
    protected $processedRecords = 0;

    /**
     * @var int
     */
    protected $successRecords = 0;

    /**
     * @var int
     */
    protected $errorRecords = 0;

    /**
     * @var int
     */
    protected $tableRowId = 0;

    /**
     * @var int
     */
    protected $rowNumber = 0;

    /**
     * @var string
     */
    protected $executionType;

    /**
     * Constructor
     */
    public function __construct(ImportService $importService, CreateObjectService $createObjectService)
    {
        $this->importService = $importService;
        $this->createObjectService = $createObjectService;
        parent::__construct();
    }

    /**
     * Configure
     */
    public function configure()
    {
        $this->setName('icecat:recurring-import')->setDescription('AUTOMATED IMPORT OF DATA FROM ICECAT');
    }

    /**
     * Execute
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->executionType = 'automatic';
        $this->configuration = Configuration::load();
        $this->db = Db::get();
        $sql = "SELECT count(*) as c FROM icecat_recurring_import WHERE status = 'running'";
        $count = $this->db->fetchRow($sql);
        if(!is_array($count)) {
            return 0;
        }
        if($count["c"] >= 1) {
            return 0;
        }

        @unlink(PIMCORE_LOG_DIRECTORY . "/" .self::LOG_FILENAME . ".log");

        $this->createOrUpdateEntryInTable();
        $this->setIcecatLoginUser();
        if($this->icecatLoginUser === null) {
            $this->createOrUpdateEntryInTable([
                "endDatetime" => time(),
                "status" => "finished",
                "totalRecords" => 0,
                "processedRecords" => 0,
                "successRecords" => 0,
                "errorRecords" => 0,
                "executionType" => $this->executionType
            ]);
            Simple::log(self::LOG_FILENAME, 'no icecat user found');
            return 1;
        }

        $assetFilePath = $this->configuration->getAssetFilePath();
        $asset = Asset::getByPath($assetFilePath);
        if($asset) {
           $this->processAssetFile($asset);
        }

        $this->createOrUpdateEntryInTable([
            "endDatetime" => time(),
            "status" => "finished",
            "totalRecords" => $this->totalRecords,
            "processedRecords" => $this->processedRecords,
            "successRecords" => $this->successRecords,
            "errorRecords" => $this->errorRecords,
            "executionType" => $this->executionType
        ]);
        Simple::log(self::LOG_FILENAME, 'INFO: import end');

        $this->db->executeQuery("DELETE FROM icecat_recurring_import WHERE id != {$this->tableRowId} AND status = 'finished'");
        return 0;
    }

    protected function processAssetFile($asset)
    {
        $spreadsheet = IOFactory::load(PIMCORE_WEB_ROOT . "/var/assets" . $asset->getFullPath());
        $sheets = $spreadsheet->getActiveSheet();
        $maxCell = $sheets->getHighestRowAndColumn();
        $excelDataArray = $sheets->rangeToArray('A1:' . $maxCell['column'] . $maxCell['row']);
        $headerArray = array_filter($excelDataArray[0]);
        $readerArray = array_change_key_case(array_flip(array_map('trim', $headerArray)), CASE_UPPER);
        $validFile = false;

        if (array_key_exists('GTIN', $readerArray)) {
            $validFile = true;
        }

        if ($validFile === false && array_key_exists('EAN', $readerArray)) {
            $validFile = true;
        }

        if($validFile === false && array_key_exists('PRODUCT CODE', $readerArray) && array_key_exists('BRAND NAME', $readerArray)) {
            $validFile = true;
        }

        if($validFile === false) {
            $this->createOrUpdateEntryInTable([
                "endDatetime" => time(),
                "status" => "finished",
                "totalRecords" => 0,
                "processedRecords" => 0,
                "successRecords" => 0,
                "errorRecords" => 0,
                "executionType" => $this->executionType
            ]);
            Simple::log(self::LOG_FILENAME, 'ERROR: file does not contain valid columns. please check sample file.');
            return 1;
        }

        // unset header
        unset($excelDataArray[0]);

        $this->rowNumber = 1;
        $this->totalRecords = count($excelDataArray);

        $this->createOrUpdateEntryInTable([
            "totalRecords" => $this->totalRecords,
        ]);
        Simple::log(self::LOG_FILENAME, "INFO: starting import with total records {$this->totalRecords}");

        foreach ($excelDataArray as $data) {
            foreach ($this->configuration->getLanguages() as $language) {

                $gtin = $productCode = $brandName = null;
                if (array_key_exists('GTIN', $readerArray)) {
                    $gtin = isset($data[$readerArray['GTIN']]) ? $data[$readerArray['GTIN']] : null;
                }

                if ($gtin === null && array_key_exists('EAN', $readerArray)) {
                    $gtin = isset($data[$readerArray['EAN']]) ? $data[$readerArray['EAN']] : null;
                }

                if (array_key_exists('PRODUCT CODE', $readerArray)) {
                    $productCode = isset($data[$readerArray['PRODUCT CODE']]) ? $data[$readerArray['PRODUCT CODE']] : null;
                }

                if (array_key_exists('BRAND NAME', $readerArray)) {
                    $brandName = isset($data[$readerArray['BRAND NAME']]) ? $data[$readerArray['BRAND NAME']] : null;
                }

                $url = null;
                if (isset($gtin) && (!empty($gtin)) && is_numeric($gtin)) {
                    $url = $this->importService->importUrl . "?UserName=$this->icecatLoginUser&Language=$language&GTIN=$gtin";
                } elseif ((isset($productCode) && !empty($productCode)) && (isset($brandName) && !empty($brandName))) {
                    $url = $this->importService . "?UserName=$this->icecatLoginUser&Language=$language&Brand=$brandName&ProductCode=$productCode";
                }

                if($url === null) {
                    Simple::log(self::LOG_FILENAME, "ERROR: ROW {$this->rowNumber} LANG {$language} - no url found");
                    continue;
                }

                try {
                    $response = $this->importService->fetchIceCatData($url);
                    $responseArray = json_decode($response, true);
                    if(array_key_exists('msg', $responseArray) && $responseArray['msg'] == 'OK') {
                        $data = [];
                        $data['gtin'] = $responseArray['data']['GeneralInfo']['IcecatId'];
                        $data['original_gtin'] = $responseArray['data']['GeneralInfo']['GTIN'][0];
                        $data['language'] = $language;
                        $data['data_encoded'] = base64_encode($response);
                        $this->createOrUpdateObject($data, $language);
                    } else {
                        ++$this->errorRecords;
                        Simple::log(self::LOG_FILENAME, "ERROR: ROW {$this->rowNumber} LANG {$language} - product not found");
                    }

                } catch (\Exception $e) {
                    ++$this->errorRecords;
                    Simple::log(self::LOG_FILENAME, "ERROR: ROW {$this->rowNumber} LANG {$language} - could not find host");
                }
            }

            ++$this->processedRecords;
            ++$this->rowNumber;

            $this->createOrUpdateEntryInTable([
                "processedRecords" => $this->processedRecords,
            ]);
        }
    }

    /**
     * @param string $data
     *
     * @return void
     */
    protected function createOrUpdateObject($data, $language)
    {
        // bootstrap
        CreateObjectService::processDataObjectFolder();
        CreateObjectService::processAssetObjectFolder();
        $this->createObjectService->setStoreId();

        $this->createObjectService->setUserId($this->icecatLoginUser);
        $this->createObjectService->setJobId(' RECURRING_IMPORT '.date("Y-m-d H:i A") . ' ');
        try {
            $this->createObjectService->createIceCatObject($data);
            Simple::log(self::LOG_FILENAME, "INFO: ROW {$this->rowNumber} LANG {$language} - processed successfully");
            ++$this->successRecords;
        } catch(\Exception $e) {
            ++$this->errorRecords;
            Simple::log(self::LOG_FILENAME, "ERROR: ROW {$this->rowNumber} LANG {$language} - {$e->getMessage()}");
        }

    }

    /**
     * Set icecat user to get used in further API calls
     *
     * @return string
     */
    protected function setIcecatLoginUser()
    {
        $sql = 'SELECT * FROM icecat_user_login ORDER BY id DESC';
        $result = $this->db->fetchRow($sql);
        if (!empty($result)) {
            $this->icecatLoginUser = $result['icecat_user_id'];
        } else {
            $this->icecatLoginUser = null;
        }
    }

    /**
     * @param array $data
     *
     * @return void
     */
    protected function createOrUpdateEntryInTable($data = [])
    {
        if(!$this->tableRowId) {
            $startDatetime = $endDatetime = time();
            $this->db->executeQuery(
                "INSERT INTO icecat_recurring_import
                (start_datetime, end_datetime, status, total_records, processed_records, success_records, error_records, execution_type)
                VALUES
                ($startDatetime, $endDatetime, 'running', 0, 0, 0, 0, 'automatic')"
            );
            $this->tableRowId = $this->db->lastInsertId();
        }

        if(count($data) === 0) {
            return;
        }

        $set = "";
        if(isset($data['startDatetime'])) {
            $set .= " start_datetime = ".$data['startDatetime']. ", ";
        }
        if(isset($data['endDatetime'])) {
            $set .= " end_datetime = ".$data['endDatetime']. ", ";
        }
        if(isset($data['status'])) {
            $set .= " status = '".$data['status']."', ";
        }
        if(isset($data['totalRecords'])) {
            $set .= " total_records = ".$data['totalRecords']. ", ";
        }
        if(isset($data['processedRecords'])) {
            $set .= " processed_records = ".$data['processedRecords']. ", ";
        }
        if(isset($data['successRecords'])) {
            $set .= " success_records = ".$data['successRecords']. ", ";
        }
        if(isset($data['errorRecords'])) {
            $set .= " error_records = ".$data['errorRecords']. ", ";
        }
        if(isset($data['executionType'])) {
            $set .= " execution_type = '".$data['executionType']."', ";
        }

        $set = rtrim($set, ', ');
        $sql = "UPDATE icecat_recurring_import SET {$set} WHERE id = {$this->tableRowId}";
        $this->db->executeQuery($sql);
    }
}
