<?php

namespace IceCatBundle\Command;

use IceCatBundle\Lib\IceCateHelper;
use IceCatBundle\Services\CreateObjectService;
use IceCatBundle\Services\ImportService;
use IceCatBundle\Model\Configuration;
use Pimcore\Console\AbstractCommand;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Icecat\Listing;
use Pimcore\Tool;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Pimcore\Log\Simple;
use Pimcore\Db\ConnectionInterface;
use Pimcore\Db;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\ClassDefinition;

class RecurringImportCommand extends AbstractCommand
{

    use IceCateHelper;

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
    public CONST LOG_FILENAME = "icecat_recurring_current_import";

    /**
     * @const string
     */
    public CONST LAST_IMPORT_LOG_FILENAME = "icecat_last_import";

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
        $this->configuration = Configuration::load();
        parent::__construct();
    }

    /**
     * Configure
     */
    public function configure()
    {
        $this->setName('icecat:recurring-import')
        ->setDescription('AUTOMATED IMPORT OF DATA FROM ICECAT')
        ->addOption(
            'execution-type',
            null,
            InputOption::VALUE_OPTIONAL,
            'Execution type ',
            'automatic'
        );
    }

    /**
     * Execute
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->executionType = $input->getOption('execution-type');
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
            Simple::log(self::LOG_FILENAME, 'ERROR: no icecat user found');
            $this->cleanup();
            return 0;
        }

        if(!is_array($this->configuration->getLanguages()) || count($this->configuration->getLanguages()) == 0) {
            $this->createOrUpdateEntryInTable([
                "endDatetime" => time(),
                "status" => "finished",
                "totalRecords" => 0,
                "processedRecords" => 0,
                "successRecords" => 0,
                "errorRecords" => 0,
                "executionType" => $this->executionType
            ]);
            Simple::log(self::LOG_FILENAME, 'ERROR: no languages configured');
            $this->cleanup();
            return 0;
        }

        $nextRun = $this->getNextCronJobExecutionTimestamp();
        if ($this->executionType == 'automatic' && ($nextRun - time()) > 0) {
            Simple::log(self::LOG_FILENAME, 'no time to run!');
            return 0;
        }

        $assetFilePath = $this->configuration->getAssetFilePath();
        $asset = Asset::getByPath($assetFilePath);
        if($asset) {
           $this->processAssetFile($asset);
        } elseif($this->configuration->getProductClass() != "") {
            $this->processClassData();
        } else {
            $this->createOrUpdateEntryInTable([
                "endDatetime" => time(),
                "status" => "finished",
                "totalRecords" => 0,
                "processedRecords" => 0,
                "successRecords" => 0,
                "errorRecords" => 0,
                "executionType" => $this->executionType
            ]);
            Simple::log(self::LOG_FILENAME, 'ERROR: neither asset file nor any class / field mappings found');
            $this->cleanup();
            return 0;
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

        $this->cleanup();

        return 0;
    }

    protected function cleanup()
    {
        $this->db->executeQuery("DELETE FROM icecat_recurring_import WHERE id != {$this->tableRowId} AND status = 'finished'");

        @unlink(PIMCORE_LOG_DIRECTORY . "/" .self::LAST_IMPORT_LOG_FILENAME . ".log");
        @rename(PIMCORE_LOG_DIRECTORY . "/" .self::LOG_FILENAME . ".log", PIMCORE_LOG_DIRECTORY . "/" .self::LAST_IMPORT_LOG_FILENAME . ".log");
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
        $this->totalRecords = count($excelDataArray) * count($this->configuration->getLanguages());

        $this->createOrUpdateEntryInTable([
            "totalRecords" => $this->totalRecords,
        ]);
        Simple::log(self::LOG_FILENAME, "INFO: starting import with total records {$this->totalRecords}");

        foreach ($excelDataArray as $data) {

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

            foreach ($this->configuration->getLanguages() as $language) {
                $url = null;
                if (isset($gtin) && (!empty($gtin)) && is_numeric($gtin)) {
                    $url = $this->importService->importUrl . "?UserName=$this->icecatLoginUser&Language=$language&GTIN=$gtin";
                } elseif ((isset($productCode) && !empty($productCode)) && (isset($brandName) && !empty($brandName))) {
                    $url = $this->importService . "?UserName=$this->icecatLoginUser&Language=$language&Brand=$brandName&ProductCode=$productCode";
                }

                if($url === null) {
                    ++$this->errorRecords;
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
                    } elseif (array_key_exists('statusCode', $responseArray)) {
                        if ($responseArray['statusCode'] == 4) {
                            Simple::log(self::LOG_FILENAME, "ERROR: ROW {$this->rowNumber} LANG {$language} - ". ImportService::REASON['PRODUCT_NOT_FOUND'] . " URL {$url}");

                        } elseif ($responseArray['statusCode'] == 2) {
                            Simple::log(self::LOG_FILENAME, "ERROR: ROW {$this->rowNumber} LANG {$language} - ". ImportService::REASON['INVALID_LANGUAGE'] . " URL {$url}");
                        }
                        ++$this->errorRecords;
                    } elseif (array_key_exists('COULD_NOT_RESOLVE_HOST', $responseArray)) {
                        Simple::log(self::LOG_FILENAME, "ERROR: ROW {$this->rowNumber} LANG {$language} - ". ImportService::REASON['COULD_NOT_RESOLVE_HOST'] . " URL {$url}");
                        ++$this->errorRecords;
                    } else {
                        ++$this->errorRecords;
                        Simple::log(self::LOG_FILENAME, "ERROR: ROW {$this->rowNumber} LANG {$language} - product not found");
                    }

                } catch (\Throwable $e) {
                    ++$this->errorRecords;
                    Simple::log(self::LOG_FILENAME, "ERROR: ROW {$this->rowNumber} LANG {$language} - could not find host");
                }
            }

            ++$this->processedRecords;
            ++$this->rowNumber;

            if($this->rowNumber % 100 == 0) {
                \Pimcore::collectGarbage();
            }

            $this->createOrUpdateEntryInTable([
                "processedRecords" => $this->processedRecords,
            ]);
        }

        return 0;
    }

    protected function processClassData()
    {
        $classId = $this->configuration->getProductClass();
        $class = ClassDefinition::getById($classId);
        $onlyNewObjectProcessed = (bool)$this->configuration->getOnlyNewObjectProcessed();
        $isGtinAvailable = $isBrandAvailable = $isProductCodeAvailable = false;
        if(trim($this->configuration->getGtinField())) {
            $isGtinAvailable = true;
        } elseif(trim($this->configuration->getBrandNameField()) && trim($this->configuration->getProductNameField())) {
            $isBrandAvailable = $isProductCodeAvailable = true;
        }

        if(!$class || (!$isGtinAvailable && (!$isBrandAvailable || !$isProductCodeAvailable))) {
            $this->createOrUpdateEntryInTable([
                "endDatetime" => time(),
                "status" => "finished",
                "totalRecords" => 0,
                "processedRecords" => 0,
                "successRecords" => 0,
                "errorRecords" => 0,
                "executionType" => $this->executionType
            ]);
            Simple::log(self::LOG_FILENAME, 'ERROR: mapping not valid. either class or fields mapping missing');
            return 1;
        }

        try {
            $listingClass = "\\Pimcore\\Model\\DataObject\\{$class->getName()}\\Listing";
            /** @var \Pimcore\Model\DataObject\Listing\Concrete $listing */
            $listing = new $listingClass();
        } catch(\Throwable $e) {
            $this->createOrUpdateEntryInTable([
                "endDatetime" => time(),
                "status" => "finished",
                "totalRecords" => 0,
                "processedRecords" => 0,
                "successRecords" => 0,
                "errorRecords" => 0,
                "executionType" => $this->executionType
            ]);
            Simple::log(self::LOG_FILENAME, "ERROR: {$e->getMessage()} {$e->getTraceAsString()}");
            return 1;
        }

        if($onlyNewObjectProcessed) {
            $sql = "SELECT * FROM icecat_recurring_import WHERE id != {$this->tableRowId} AND status = 'finished'";
            $result = $this->db->fetchRow($sql);
            if($result) {
                $startDatetime = $result['start_datetime'];
                $listing->setCondition("o_creationDate > {$startDatetime}");
            }
        }

        if($listing->count() === 0) {
            $this->createOrUpdateEntryInTable([
                "endDatetime" => time(),
                "status" => "finished",
                "totalRecords" => 0,
                "processedRecords" => 0,
                "successRecords" => 0,
                "errorRecords" => 0,
                "executionType" => $this->executionType
            ]);
            Simple::log(self::LOG_FILENAME, 'ERROR: no records found');
            return 1;
        }

        $this->rowNumber = 1;
        $this->totalRecords = $listing->count() * count($this->configuration->getLanguages());

        $this->createOrUpdateEntryInTable([
            "totalRecords" => $this->totalRecords,
        ]);
        Simple::log(self::LOG_FILENAME, "INFO: starting import with total records {$this->totalRecords}");

        $ids = $listing->loadIdList();
        foreach ($ids as $pimcoreId) {
            $object = AbstractObject::getById($pimcoreId);
            foreach ($this->configuration->getLanguages() as $language) {
                $url = null;
                if ($isGtinAvailable) {
                    $getter = "get".ucfirst($this->configuration->getGtinField());
                    if($this->configuration->getGtinFieldType() == "manyToOneRelation") {
                        if(trim($this->configuration->getMappingGtinClassField()) == "") {
                            ++$this->errorRecords;
                            Simple::log(self::LOG_FILENAME, "ERROR: PIMCORE ID {$object->getId()} LANG {$language} - gtin reference field mapping missing");
                            continue;
                        }
                        $referenceGetter = "get".ucfirst($this->configuration->getMappingGtinClassField());
                        try {
                            $gtin = $object->$getter()->$referenceGetter($language);
                        } catch(\Throwable $e) {
                            ++$this->errorRecords;
                            Simple::log(self::LOG_FILENAME, "ERROR: PIMCORE ID {$object->getId()} LANG {$language} - {$e->getMessage()} {$e->getTraceAsString()}");
                            continue;
                        }
                    } else {
                        try {
                            $gtin = $object->$getter($language);
                        } catch(\Throwable $e) {
                            ++$this->errorRecords;
                            Simple::log(self::LOG_FILENAME, "ERROR: PIMCORE ID {$object->getId()} LANG {$language} - {$e->getMessage()} {$e->getTraceAsString()}");
                            continue;
                        }
                    }
                    $url = $this->importService->importUrl . "?UserName=$this->icecatLoginUser&Language=$language&GTIN=$gtin";
                } else {

                    // Brand
                    $getter = "get".ucfirst($this->configuration->getBrandNameField());
                    if($this->configuration->getBrandNameFieldType() == "manyToOneRelation") {
                        if(trim($this->configuration->getMappingBrandClassField()) == "") {
                            ++$this->errorRecords;
                            Simple::log(self::LOG_FILENAME, "ERROR: PIMCORE ID {$object->getId()} LANG {$language} - brand reference field mapping missing");
                            continue;
                        }
                        $referenceGetter = "get".ucfirst($this->configuration->getMappingBrandClassField());
                        try {
                            $brand = $object->$getter()->$referenceGetter($language);
                        } catch(\Throwable $e) {
                            ++$this->errorRecords;
                            Simple::log(self::LOG_FILENAME, "ERROR: PIMCORE ID {$object->getId()} LANG {$language} - {$e->getMessage()} {$e->getTraceAsString()}");
                            continue;
                        }
                    } else {
                        try {
                            $brand = $object->$getter($language);
                        } catch(\Throwable $e) {
                            ++$this->errorRecords;
                            Simple::log(self::LOG_FILENAME, "ERROR: PIMCORE ID {$object->getId()} LANG {$language} - {$e->getMessage()} {$e->getTraceAsString()}");
                            continue;
                        }
                    }

                    // ProductCode
                    $getter = "get".ucfirst($this->configuration->getProductNameField());
                    if($this->configuration->getProductNameFieldType() == "manyToOneRelation") {
                        if(trim($this->configuration->getMappingProductCodeClassField()) == "") {
                            ++$this->errorRecords;
                            Simple::log(self::LOG_FILENAME, "ERROR: PIMCORE ID {$object->getId()} LANG {$language} - product code reference field mapping missing");
                            continue;
                        }
                        $referenceGetter = "get".ucfirst($this->configuration->getMappingProductCodeClassField());
                        try {
                            $productCode = $object->$getter()->$referenceGetter($language);
                        } catch(\Throwable $e) {
                            ++$this->errorRecords;
                            Simple::log(self::LOG_FILENAME, "ERROR: PIMCORE ID {$object->getId()} LANG {$language} - {$e->getMessage()} {$e->getTraceAsString()}");
                            continue;
                        }
                    } else {
                        try {
                            $productCode = $object->$getter($language);
                        } catch(\Throwable $e) {
                            ++$this->errorRecords;
                            Simple::log(self::LOG_FILENAME, "ERROR: PIMCORE ID {$object->getId()} LANG {$language} - {$e->getMessage()} {$e->getTraceAsString()}");
                            continue;
                        }
                    }

                    $url = $this->importService->importUrl . "?UserName=$this->icecatLoginUser&Language=$language&Brand=$brand&ProductCode=$productCode";
                }

                if($url === null) {
                    ++$this->errorRecords;
                    Simple::log(self::LOG_FILENAME, "ERROR: PIMCORE ID {$object->getId()} LANG {$language} - no url found");
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
                        $this->createOrUpdateObject($data, $language, $object);
                    } elseif (array_key_exists('statusCode', $responseArray)) {
                        if ($responseArray['statusCode'] == 4) {
                            Simple::log(self::LOG_FILENAME, "ERROR: PIMCORE ID {$object->getId()} LANG {$language} - ". ImportService::REASON['PRODUCT_NOT_FOUND'] . " URL {$url}");

                        } elseif ($responseArray['statusCode'] == 2) {
                            Simple::log(self::LOG_FILENAME, "ERROR: PIMCORE ID {$object->getId()} LANG {$language} - ". ImportService::REASON['INVALID_LANGUAGE'] . " URL {$url}");
                        }
                        ++$this->errorRecords;
                    } elseif (array_key_exists('COULD_NOT_RESOLVE_HOST', $responseArray)) {
                        Simple::log(self::LOG_FILENAME, "ERROR: PIMCORE ID {$object->getId()} LANG {$language} - ". ImportService::REASON['COULD_NOT_RESOLVE_HOST'] . " URL {$url}");
                        ++$this->errorRecords;
                    } else {
                        ++$this->errorRecords;
                        Simple::log(self::LOG_FILENAME, "ERROR: PIMCORE ID {$object->getId()} LANG {$language} - product not found" . " URL {$url}");
                    }

                } catch (\Throwable $e) {
                    ++$this->errorRecords;
                    Simple::log(self::LOG_FILENAME, "ERROR: PIMCORE ID {$object->getId()} LANG {$language} - {$e->getMessage()} {$e->getTraceAsString()}");
                }
            }

            ++$this->processedRecords;
            ++$this->rowNumber;

            if($this->rowNumber % 100 == 0) {
                \Pimcore::collectGarbage();
            }

            $this->createOrUpdateEntryInTable([
                "processedRecords" => $this->processedRecords,
            ]);
        }

        return 0;
    }

    public function processUserProducts()
    {
        $validConfigToProceed = false;
        $productClass = $this->configuration->getProductClass();
        if (empty($productClass)) {
            p_r('Product class not set!!');
            $validConfigToProceed = false;
        }
        $gtinField = $this->configuration->getGtinField();
        $brandNameField = $this->configuration->getBrandNameField();
        $productCodeField = $this->configuration->getProductNameField();

        if (empty($gtinField) && (empty($brandNameField) || empty($productNameField))) {
            p_r('Either GTIN or (brandName and productName) field(s) must be set!!');
            $validConfigToProceed = false;
        }

        if (!$validConfigToProceed) {
                $this->createOrUpdateEntryInTable([
                "endDatetime" => time(),
                "status" => "finished",
                "totalRecords" => 0,
                "processedRecords" => 0,
                "successRecords" => 0,
                "errorRecords" => 0,
                "executionType" => $this->executionType
            ]);
            Simple::log(self::LOG_FILENAME, 'Product class not set!! | Either GTIN or (brandName and productName) field(s) must be set!!');
            return 1;
        }

        $onlyNewObjectProcessed = $this->configuration->getOnlyNewObjectProcessed();
        if ($onlyNewObjectProcessed) {
            $lastCronJobExecutionEndTime = $this->getLastCronJobExecutionEndTime();
        }

        $listingClass = "\\Pimcore\\Model\\DataObject\\" . $productClass . '\\Listing';
        /** @var Listing $listing */
        $listing = new $listingClass();
        if ($onlyNewObjectProcessed) {
            $listing->setCondition('o_modificationDate >=?' . [$lastCronJobExecutionEndTime]);
        }

        $batchSize = 500;
        $this->totalRecords = $listing->count();

        if ($this->totalRecords) {
            if (!$validConfigToProceed) {
                $this->createOrUpdateEntryInTable([
                    "endDatetime" => time(),
                    "status" => "finished",
                    "totalRecords" => 0,
                    "processedRecords" => 0,
                    "successRecords" => 0,
                    "errorRecords" => 0,
                    "executionType" => $this->executionType
                ]);
                Simple::log(self::LOG_FILENAME, 'No User products found to process');
                return 1;
            }
        }
        $totalIteration = ceil($this->totalRecords  / $batchSize);

            // @todo: need to add listing condition

        for($counter = 0; $counter < $totalIteration; $counter++) {
            $listing->setLimit($batchSize);
            $listing->setOffset($counter * $batchSize);
            $productsList = $listing->load();
            foreach ($productsList as $product) {
                foreach ($this->configuration->getLanguages() as $language) {
                    try {
                        $res = $this->getIceCatData($product, $gtinField, $brandNameField, $productCodeField, $language);
                        if ($res['failure']) {
                            ++$this->errorRecords;
                            Simple::log(self::LOG_FILENAME, "ERROR: in fetch data for product {$product} with
                        values({$gtinField}, {$brandNameField}, {$productCodeField}) LANG {$language} - product not found");
                            continue;
                        }
                        $this->createOrUpdateObject($res['iceCatData'], $language);
                    } catch (\Exception $e) {
                        ++$this->errorRecords;
                        Simple::log(self::LOG_FILENAME, "ERROR: in fetch data for product {$product} with
                        values({$gtinField}, {$brandNameField}, {$productCodeField}) LANG {$language} - " . $e->getMessage());;
                    }
                }
            }
        }


            ++$this->processedRecords;

            $this->createOrUpdateEntryInTable([
                "processedRecords" => $this->processedRecords,
            ]);

    }

    public function getIceCatData($product, $gtinField, $brandNameField, $productCodeField, $language)
    {
        if (!empty($gtinField)) {
            $gtin = $product->{'get' . ucfirst($gtinField)}();
            if (is_object($gtinField)) {
                $referenceField = $this->configuration->getMappingGtinClassField();
                if (!empty($referenceField)) {
                    $gtin = $gtin->{'get' . ucfirst($referenceField)}();
                } else {
                    $gtin = '';
                }
            }
        }

        if (!empty($brandNameField)) {
            $brandName = $product->{'get' . ucfirst($brandNameField)}();
            if (is_object($brandName)) {
                $referenceField = $this->configuration->getMappingGtinClassField();
                if (!empty($referenceField)) {
                    $brandName = $brandName->{'get' . ucfirst($referenceField)}();
                } else {
                    $brandName = '';
                }
            }
        }

        if (!empty($productCodeField)) {
            $productCode = $product->{'get' . ucfirst($productCodeField)}();
            if (is_object($gtinField)) {
                $referenceField = $this->configuration->getMappingGtinClassField();
                if (!empty($referenceField)) {
                    $productCode = $productCode->{'get' . ucfirst($referenceField)}();
                } else {
                    $productCode = '';
                }
            }
        }
        $dataToFetchIceProduct['gtin'] = $gtin;
        $dataToFetchIceProduct['productCode'] = $productCode;
        $dataToFetchIceProduct['brandName'] = $brandName;

        $result = [];
        $url = $this->getIceCatUrlToGetRecord($dataToFetchIceProduct, $this->icecatLoginUser, $language);
        if ($url == -1) {
            $reason = ImportService::REASON['INVALID_KEY'];
            $result =  [ 'failure' => true, 'msg' => $reason];
        }

        try {
            p_r('url to fetch =>' . $url);
            $response = $this->fetchIceCatData($url);
            $responseArray = json_decode($response, true);
        } catch (\Exception $e) {
            // IN CASE OF INTERNET ACCESSIBLITY IS NOT AVAILABEL OR ICE CAT'S SERVER IS DOWN
            $response = '';
            $responseArray['COULD_NOT_RESOLVE_HOST'] = true;
        }

        $productName = '';
        if (array_key_exists('statusCode', $responseArray)) {
            if ($responseArray['statusCode'] == 4) {
                $reason = ImportService::REASON['PRODUCT_NOT_FOUND'];
                $result =  [ 'failure' => true, 'msg' => $reason];
            } elseif ($responseArray['statusCode'] == 2) {
                $reason = ImportService::REASON['INVALID_LANGUAGE'];
                $result =  [ 'failure' => true, 'msg' => $reason];
            }
        } elseif (array_key_exists('COULD_NOT_RESOLVE_HOST', $responseArray)) {
            $reason = ImportService::REASON['COULD_NOT_RESOLVE_HOST'];
            $result =  [ 'failure' => true, 'msg' => $reason];
        } elseif (array_key_exists('msg', $responseArray) && $responseArray['msg'] == 'OK') {
            //Product Found
            $gtin = $responseArray['data']['GeneralInfo']['GTIN'][0];
            $currentProductIceCatId = $responseArray['data']['GeneralInfo']['IcecatId'];
            $productName = $productName = str_replace("'", "''", $responseArray['data']['GeneralInfo']['ProductName']);

            $data = [
                'gtin' => $gtin,
                'original_gtin' => $currentProductIceCatId,
                'language' => $this->language,
                'data_encoded' => base64_encode($response),
                'product_name' => $productName
            ];
            $result =  [ 'failure' => false, 'iceCatData' => $data];
        }
        return $result;
    }

    /**
     * @param string $data
     * @param string $language
     * @param AbstractObject $pimcoreObject
     *
     * @return void
     */
    protected function createOrUpdateObject($data, $language, $pimcoreObject = null)
    {
        // bootstrap
        CreateObjectService::processDataObjectFolder();
        CreateObjectService::processAssetObjectFolder();
        $this->createObjectService->setStoreId();
        $this->createObjectService->setUserId($this->icecatLoginUser);
        $this->createObjectService->setJobId(' RECURRING_IMPORT '.date("Y-m-d H:i A") . ' ');

        try {
            $this->createObjectService->createIceCatObject($data);
            if($pimcoreObject) {
                Simple::log(self::LOG_FILENAME, "INFO: PIMCORE ID {$pimcoreObject->getId()} LANG {$language} - processed successfully");
            } else {
                Simple::log(self::LOG_FILENAME, "INFO: ROW {$this->rowNumber} LANG {$language} - processed successfully");
            }

            ++$this->successRecords;
        } catch(\Throwable $e) {
            ++$this->errorRecords;

            if($pimcoreObject) {
                Simple::log(self::LOG_FILENAME, "INFO: PIMCORE ID {$pimcoreObject->getId()} LANG {$language} - processed successfully");
            } else {
                Simple::log(self::LOG_FILENAME, "INFO: ROW {$this->rowNumber} LANG {$language} - processed successfully");
            }
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
                ($startDatetime, $endDatetime, 'running', 0, 0, 0, 0, '{$this->executionType}')"
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
