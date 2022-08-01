<?php

namespace IceCatBundle\Services;

use IceCatBundle\Lib\IceCateHelper;
use IceCatBundle\Model\Configuration;
use Pimcore\Log\ApplicationLogger;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Data\BlockElement;
use Pimcore\Model\DataObject\Icecat;
use Pimcore\Model\DataObject\Localizedfield;

class CreateObjectService
{
    use IceCateHelper;
    const DATAOBJECT_FOLDER = 'ICECAT';
    const CAT_DATAOBJECT_FOLDER = 'ICECAT/CATEGORIES';
    const ASSET_FOLDER = 'ICECAT';
    const JOB_DATA_CONTAINER_TABLE = 'ice_cat_processes';
    const IMPORTED_DATA_CONTAINER_TABLE = 'icecat_imported_data';
    const LOG_STATUS = [
        'SUCCESSFUL' => 'SUCCESSFUL',
        'ERROR' => 'ERROR',
    ];
    const APPLOGGER_PREFIX = 'ICECAT';
    const PROCESS_TYPE = [
        'OBJECT_CREATION' => 'OBJECT-CREATION',
        'DATA_IMPORT' => 'DATA-IMPORT'
    ];

    private $logger;
    protected $csvLogger;
    protected $csvLogMessage = [];
    protected $logMessage;
    private $csvLogFileName;
    protected $appLogger;
    protected $modifiedFields;

    public static $recursiveFlag = true;

    protected $fieldNameMappingWithDataKey = [
        'AwardHighPic' => 'awardHighPic',
        'AwardLogoPic' => 'awardLogoPic',
        'LogoPic' => 'logoPic',
    ];

    /**
     * @var Configuration|null
     */
    protected $config;

    private $ClassName;
    private $iceCatClass = 'Icecat';
    private $storeId;
    private $groupId;
    private $keyId;
    private $valueToBeInsert;
    private $unitPrefix = 'icecat';
    private $dataTypes = [
        'numerical' => 'QuantityValue',
        'dropdown' => 'Select',
        'textarea' => 'Textarea',
        'alphanumeric' => 'InputQuantityValue',
        'range' => 'InputQuantityValue',
        'multi_dropdown' => 'Multiselect',
        'y_n' => 'BooleanSelect',
        '3d' => 'InputQuantityValue',
        '2d' => 'InputQuantityValue',
        'text' => 'Input',
        'contrast ratio' => 'Input'
    ];

    protected $jobId;
    protected $currentProductId;
    protected $currentGtin;

    protected $processingError = [];
    private $status = [
        'PROCESSING' => 'PROCESSING',
        'PROCESSED' => 'PROCESSED',

    ];

    protected $importService;
    protected $userId;
    private $quantityUnitId;

    protected $currentDateTimeStamp;
    protected $currentFeatureGroup = [];
    protected $jobHandler;
    protected $currentLanguage = 'en';

    public function __construct(
        IceCatLogger $logger,
        IceCatCsvLogger $csvLogger,
        ApplicationLogger $appLogger,
        JobHandlerService $jobHandler,
        ImportService $importService
    ) {
        $this->logger = $logger;
        $this->csvLogger = $csvLogger;
        $this->appLogger = $appLogger;
        $this->jobHandler = $jobHandler;
        $this->importService = $importService;
        $this->config = Configuration::load();
    }

    public function setStoreId()
    {
        $db = \Pimcore\Db::get();
        $query = "SELECT id FROM `classificationstore_stores` WHERE `name` = 'icecat-store' ";
        $data = $db->fetchRow($query);
        $this->storeId = $data['id'];
    }

    public function updateCurrentProcess($table, $updateArray, $identifierCol, $identifierVal)
    {
        $db = \Pimcore\Db::get();
        $updateCols = '';
        foreach ($updateArray as $key => $value) {
            $updateCols .= " $key  =   '$value' ,";
        }
        $updateCols = rtrim($updateCols, ',');
        $updateQuery = 'UPDATE ' . $table . " SET $updateCols
        WHERE $identifierCol =  '$identifierVal' ";
        $db->exec($updateQuery);
    }

    public static function processDataObjectFolder()
    {
        if (!\Pimcore\Model\DataObject\Service::pathExists('/' . self::DATAOBJECT_FOLDER)) {
            $folder = \Pimcore\Model\DataObject\Folder::create([
                'o_parentId' => 1,
                'o_creationDate' => time(),
                'o_key' => self::DATAOBJECT_FOLDER,
                'o_published' => true,
            ]);
            $folder->save();
        }
    }

    public static function processAssetObjectFolder()
    {
        if (!\Pimcore\Model\Asset\Service::pathExists('/' . self::ASSET_FOLDER)) {
            $assetFolder = Asset::create(1, [
                'filename' => self::ASSET_FOLDER,
                'type' => 'folder',

            ]);
            $assetFolder->save();
        }
    }

    public function CreateObject($userId, $jobId)
    {
        $this->csvLogFileName = date('Y-m-d H:i:s');
        $this->csvLogMessage = [];
        $counter = 0;
        $importData = $this->getImportArray($jobId);

        if (!$importData) {
            // $this->logMessage = 'NOTHING TO IMPORT TERMINATING OBJECT CREATION FOR JOB ID :' . $jobId;
            // $this->logger->addLog('create-object', $this->logMessage, '', 'INFO');

            // // Updating import to be completed
            // $updateArray = ['completed' => 1];
            // $this->updateCurrentProcess(self::JOB_DATA_CONTAINER_TABLE, $updateArray, 'jobid', $jobId);

            // return 0;
        }

        try {
            //Processing folders
            self::processDataObjectFolder();
            self::processAssetObjectFolder();
            //setting icecat store id
            $this->setStoreId();

            $iceCatClass = '\Pimcore\Model\DataObject\\' . $this->iceCatClass;
            // Updating processing status and Total Records
            $updateArray = ['total_records' => count($importData), 'processed_records' => 0, 'processing_status' => $this->status['PROCESSING']];
            $this->updateCurrentProcess(self::JOB_DATA_CONTAINER_TABLE, $updateArray, 'jobid', $jobId);
            foreach ($importData as $data) {
                $this->userId = $data['icecat_username'];
                $time_start = microtime(true);
                try {
                    $this->jobId = $jobId;

                    // $isJobAlive = $this->jobHandler->isLive($this->jobId);
                    // if ($isJobAlive === false) {
                    //     $this->logMessage = 'JOB TERMINATED FROM FRONTEND:' . $this->jobId;
                    //     $this->logger->addLog('create-object', $this->logMessage, '', 'INFO');

                    //     return true;
                    // }
                    //\Pimcore\Cache::clearAll();

                    $this->logMessage = 'STARTING OBJECT CREATION FOR JOB ID : ' . $this->jobId . ' AND PRODUCT ID :' . $this->currentProductId;
                    $this->logger->addLog('create-object', $this->logMessage, '', 'INFO');
                    self::$recursiveFlag = true;

                    $iceCatobject = $this->createIceCatObject($data);

                    ++$counter;
                    // Updating Processed Record
                    $this->logMessage = ' BEFORE UPDATE:' .    $counter;
                    $this->logger->addLog('create-object', $this->logMessage, 'INFO');
                    $updateArray = ['processed_records' => $counter];
                    $this->updateCurrentProcess(self::JOB_DATA_CONTAINER_TABLE, $updateArray, 'jobid', $jobId);
                    // Setting Product to be Processed
                    $updateArray = ['is_product_proccessed' => 1];
                    $this->updateCurrentProcess(self::IMPORTED_DATA_CONTAINER_TABLE, $updateArray, 'id', $data['id']);

                    // Object Creation successful
                    $this->logMessage = 'OBJECT CREATION SUCCESSFUL FOR JOB ID :' . $this->jobId . ' AND PRODUCT ID :' . $this->currentProductId . 'id:' . $iceCatobject->getId();
                    $this->logger->addLog('create-object', $this->logMessage, '', 'INFO');

                    $this->csvLogger->addLogRow($this->currentProductId, self::LOG_STATUS['SUCCESSFUL'], ['OBJECT CREATED SUCESSFULLY'], $this->csvLogFileName);

                    $appLoggerMessage = "OBJECT CREATION SUCCESSFUL FOR JOB ID :  $this->jobId   AND PRODUCT ID : . $this->currentProductId";
                    $this->appLogger->info($appLoggerMessage, [
                        'component' => self::APPLOGGER_PREFIX . ' ' . self::PROCESS_TYPE['OBJECT_CREATION'],
                        'relatedObject' => $iceCatobject
                    ]);
                } catch (\Throwable $e) {
                    ++$counter;
                    // Updating Processed Record
                    $updateArray = ['processed_records' => $counter];
                    $this->updateCurrentProcess(self::JOB_DATA_CONTAINER_TABLE, $updateArray, 'jobid', $this->jobId);
                    $this->csvLogMessage[] = 'ERROR IN OBJECT CREATION :' . $e->getMessage();
                    $this->csvLogger->addLogRow($this->currentProductId, self::LOG_STATUS['ERROR'], $this->csvLogMessage, $this->csvLogFileName);
                    $this->csvLogger->saveLog($this->csvLogFileName, 'OBJECT_CREATE');
                    $this->logMessage = 'ERROR IN  OBJECT CREATION FOR JOB ID :' . $this->jobId . ' AND PRODUCT ID :' . $this->currentProductId . '-' . $e->getMessage();
                    $this->logger->addLog('create-object', $this->logMessage, $e->getTraceAsString(), 'ERROR');

                    $appLoggerMessage = "ERROR IN  OBJECT CREATION FOR  PRODUCT ID  $this->currentProductId - " . $e->getMessage();
                    $this->appLogger->error($appLoggerMessage, [
                        'component' => self::APPLOGGER_PREFIX . ' ' . self::PROCESS_TYPE['OBJECT_CREATION'],
                        'relatedObject' => $iceCatobject
                    ]);
                }
                $time_end = microtime(true);
                $execution_time = ($time_end - $time_start);

                // //execution time of the script
                // echo '<b>Total Execution Time:</b> '.$execution_time.' sec';
                // // if you get weird results, use number_format((float) $execution_time, 10)
                // die;
            }

            $updateArray = ['completed' => 1, 'processing_error ' => (!empty($this->processingError)) ? json_encode($this->processingError) : 'NO-ERROR', 'processing_status' => $this->status['PROCESSED']];
            $this->updateCurrentProcess(self::JOB_DATA_CONTAINER_TABLE, $updateArray, 'jobid', $jobId);

            $this->csvLogger->saveLog($this->csvLogFileName, 'OBJECT_CREATE');
            $this->logMessage = ' OBJECT CREATION COMPLETED JOB ID :' . $jobId;
            $this->logger->addLog('create-object', $this->logMessage, 'INFO');
        } catch (\Exception $e) {
            $this->csvLogger->addLogRow($this->currentProductId, self::LOG_STATUS['ERROR'], $this->csvLogMessage, $this->csvLogFileName);
            $this->csvLogMessage[] = 'ERROR IN OBJECT CREATION : ' . $e->getMessage();
            $this->csvLogger->saveLog($this->csvLogFileName, 'OBJECT_CREATE');

            $this->processingError[] = $e->getMessage();
            // Updating processing status and processing error (if no error it will be empty)
            $updateArray = ['completed' => 1, 'processing_error ' => json_encode($this->processingError), 'processing_status' => $this->status['PROCESSED']];
            $this->updateCurrentProcess(self::JOB_DATA_CONTAINER_TABLE, $updateArray, 'jobid', $jobId);

            $this->logMessage = 'ERROR IN JOB CREATION FOR JOB ID :' . $jobId . 'AND PRODUCT ID :' . $this->currentProductId . '-' . $e->getMessage();
            $this->logger->addLog('create-object', $this->logMessage, $e->getTraceAsString(), 'ERROR');

            $appLoggerMessage = "OBJECT CREATION TERMINATED FOR  $jobId  ";
            $this->appLogger->error($appLoggerMessage, [
                'component' => self::APPLOGGER_PREFIX . ' ' . self::PROCESS_TYPE['OBJECT_CREATION'],

            ]);
        }
    }

    public function createIceCatObject($data)
    {
        $iceCatClass = '\Pimcore\Model\DataObject\\' . $this->iceCatClass;
        $this->currentProductId = $data['gtin'];
        $this->currentGtin = $data['original_gtin'];
        $this->currentLanguage = $data['language'];




        $importArray = json_decode(base64_decode($data['data_encoded']), true);
        if (empty($iceCatClass::getByPath('/' . self::DATAOBJECT_FOLDER . '/' . $this->currentProductId))) {
            /** @var \Pimcore\Model\DataObject\Icecat $iceCatobject */
            $iceCatobject = new $iceCatClass();
            $iceCatobject->setPublished(true);
            $iceCatobject->setKey($this->currentProductId);
            $iceCatobject->setParent(\Pimcore\Model\DataObject::getByPath('/' . self::DATAOBJECT_FOLDER));
            $this->modifiedFields = [];
            $this->createFixFields($importArray['data'], $iceCatobject);
            $this->createGallery($importArray['data'], $iceCatobject);
            $this->createDynamicFields($importArray['data'], $iceCatobject);
        } else {
            /** @var \Pimcore\Model\DataObject\Icecat $iceCatobject */
            $iceCatobject = $iceCatClass::getByPath('/' . self::DATAOBJECT_FOLDER . '/' . $this->currentProductId);
            $this->modifiedFields = $this->getFieldsModifiedLog($iceCatobject->getId(), $this->currentLanguage);
            $this->createFixFields($importArray['data'], $iceCatobject);
            $this->createGallery($importArray['data'], $iceCatobject);
            $this->createDynamicFields($importArray['data'], $iceCatobject);
        }

        if (!is_array($iceCatobject->getRelatedCategories()) || count($iceCatobject->getRelatedCategories()) === 0) {
            $iceCatobject->setCategorization(null);
        } else {
            $iceCatobject->setCategorization(true);
        }

        $iceCatobject->save();
        return $iceCatobject;
    }

    /***
     * Mehtods : fetch the object json from db,and
     * return it in array format
     */
    public function getImportArray($id)
    {
        $db = \Pimcore\Db::get();
        $query = 'select * from ' . self::IMPORTED_DATA_CONTAINER_TABLE . " where job_id = '$id'  and is_product_found = 1 and to_be_created = 1";
        $data = $db->fetchAll($query);

        return $data;
    }

    /**
     * @param $attributeArray
     * @param Icecat $iceCatobject
     */
    public function createFixFields($attributeArray, $iceCatobject)
    {
        try {
            $this->logMessage = 'STARTING FIX FIELD CREATION FOR JOB ID :' . $this->jobId . 'AND PRODUCT ID :' . $this->currentProductId;
            $this->logger->addLog('create-object', $this->logMessage, '', 'INFO');


            $basicInformation = $attributeArray['GeneralInfo'];
            if (!$this->isFieldUpdatedByUser('Product_Name', $this->currentLanguage)) {
                $iceCatobject->setProduct_Name($basicInformation['ProductName'], $this->currentLanguage);
            }
            if (!$this->isFieldUpdatedByUser('Product_Code', $this->currentLanguage)) {
                $iceCatobject->setProduct_Code($basicInformation['BrandPartCode'], $this->currentLanguage);
            }
            if (!$this->isFieldUpdatedByUser('Brand', $this->currentLanguage)) {
                $iceCatobject->setBrand($basicInformation['Brand'], $this->currentLanguage);
            }

            if (!$this->isFieldUpdatedByUser('Category', $this->currentLanguage)) {
                $iceCatobject->setCategory($basicInformation['Category']['Name']['Value'], $this->currentLanguage);
            }

            if (!$this->isFieldUpdatedByUser('ProductName', $this->currentLanguage)) {
                $iceCatobject->setInfo_Modified_On($basicInformation['ReleaseDate'], $this->currentLanguage);
            }

            if (!$this->isFieldUpdatedByUser('Icecat_Product_Id', $this->currentLanguage)) {
                $iceCatobject->setIcecat_Product_Id($basicInformation['IcecatId'], $this->currentLanguage);
            }

            if (!$this->isFieldUpdatedByUser('Long_Summary', $this->currentLanguage)) {
                $iceCatobject->setLong_Summary($basicInformation['SummaryDescription']['LongSummaryDescription'], $this->currentLanguage);
            }

            if (!$this->isFieldUpdatedByUser('Short_Summary', $this->currentLanguage)) {
                $iceCatobject->setShort_Summary($basicInformation['SummaryDescription']['ShortSummaryDescription'], $this->currentLanguage);
            }
            if (!$this->isFieldUpdatedByUser('productTitle', $this->currentLanguage)) {
                $iceCatobject->setProductTitle($basicInformation['Title'], $this->currentLanguage);
            }
            if (!$this->isFieldUpdatedByUser('Gtin', $this->currentLanguage)) {
                $iceCatobject->setGtin($this->currentGtin, $this->currentLanguage);
            }
            if (!$this->isFieldUpdatedByUser('brandPartCode', $this->currentLanguage)) {
                $iceCatobject->setBrandPartCode($basicInformation['BrandPartCode'], $this->currentLanguage);
            }
            //$iceCatobject->setReleaseDate($this->getCarbonObjectForDateString($basicInformation['release_date']), $this->currentLanguage);
            if (!$this->isFieldUpdatedByUser('endOfLifeDate', $this->currentLanguage)) {
                $iceCatobject->setEndOfLifeDate($this->getCarbonObjectForDateString($basicInformation['EndOfLifeDate']), $this->currentLanguage);
            }

            if (!$this->isFieldUpdatedByUser('longDescription', $this->currentLanguage) && isset($basicInformation['Description']['LongDesc'])) {
                $iceCatobject->setLongDescription($basicInformation['Description']['LongDesc'], $this->currentLanguage);
            }

            if (!$this->isFieldUpdatedByUser('middleDescription', $this->currentLanguage) && isset($basicInformation['Description']['MiddleDesc'])) {
                $iceCatobject->setMiddleDescription($basicInformation['Description']['MiddleDesc'], $this->currentLanguage);
            }

            if (!$this->isFieldUpdatedByUser('Disclaimer', $this->currentLanguage) && isset($basicInformation['Description']['Disclaimer'])) {
                $iceCatobject->setDisclaimer($basicInformation['Description']['Disclaimer'], $this->currentLanguage);
            }

            if (!$this->isFieldUpdatedByUser('manualPDFURL', $this->currentLanguage) && isset($basicInformation['Description']['ManualPDFURL'])) {
                $iceCatobject->setManualPDFURL($basicInformation['Description']['ManualPDFURL'], $this->currentLanguage);
            }

            if (!$this->isFieldUpdatedByUser('leafletPDFURL', $this->currentLanguage) && isset($basicInformation['Description']['LeafletPDFURL'])) {
                $iceCatobject->setLeafletPDFURL($basicInformation['Description']['LeafletPDFURL'], $this->currentLanguage);
            }

            if (!$this->isFieldUpdatedByUser('url', $this->currentLanguage) && isset($basicInformation['Description']['URL'])) {
                $iceCatobject->setUrl($basicInformation['Description']['URL'], $this->currentLanguage);
            }

            if (!$this->isFieldUpdatedByUser('warrantyInfo', $this->currentLanguage) && isset($basicInformation['Description']['WarrantyInfo'])) {
                $iceCatobject->setWarrantyInfo($basicInformation['Description']['WarrantyInfo'], $this->currentLanguage);
            }
            if (!$this->isFieldUpdatedByUser('productLongName', $this->currentLanguage) && isset($basicInformation['Description']['LongProductName'])) {
                $iceCatobject->setProductLongName($basicInformation['Description']['LongProductName'], $this->currentLanguage);
            }

            if (!$this->isFieldUpdatedByUser('productFamily', $this->currentLanguage) && isset($basicInformation['ProductFamily']['Value'])) {
                $iceCatobject->setProductFamily($basicInformation['ProductFamily']['Value'], $this->currentLanguage);
            }

            if (!$this->isFieldUpdatedByUser('productSeries', $this->currentLanguage) && isset($basicInformation['ProductSeries']['Value'])) {
                $iceCatobject->setProductSeries($basicInformation['ProductSeries']['Value'], $this->currentLanguage);
            }

            if (!$this->isFieldUpdatedByUser('BulletPoints', $this->currentLanguage) &&
                isset($basicInformation['BulletPoints']['Values'])) {
                $bulletPointsArray = $basicInformation['BulletPoints']['Values'];
                $bulletHtml = '<ul>';
                foreach ($bulletPointsArray as $bullet) {
                    $bulletHtml .= '<li>' . $bullet . '</li>';
                }
                $bulletHtml .= '</ul>';
                $iceCatobject->setBulletPoints($bulletHtml, $this->currentLanguage);
            }

            if (!$this->isFieldUpdatedByUser('productStory', $this->currentLanguage)) {
                $this->setProductStoryData($attributeArray['ProductStory'], $iceCatobject);
            }

            if (!$this->isFieldUpdatedByUser('brandLogo')) {
                $this->createBrandLogo($basicInformation, $iceCatobject);
            }

            if (!$this->isFieldUpdatedByUser('Reasons_to_buy', $this->currentLanguage)) {
                $this->createReasonsToBuy($attributeArray, $iceCatobject);
            }

            if (!$this->isFieldUpdatedByUser('Tour')) {
                // Insert Images in 3d Tour fields if it is available
                $this->create3dTourField($attributeArray['Multimedia'], $iceCatobject);
            }

            if (!$this->isFieldUpdatedByUser('videos')) {
                // Insert Videos in video fields if it is available
                $this->createVideoField($attributeArray['Multimedia'], $iceCatobject);
            }
            if (!$this->isFieldUpdatedByUser('storyUrl', $this->currentLanguage)) {
                $this->setStoryField($attributeArray, $iceCatobject);
            }

            if (!$this->isFieldUpdatedByUser('multiMedia', $this->currentLanguage)) {
                $this->setMultiMedia($attributeArray, $iceCatobject);
            }
            if (!$this->isFieldUpdatedByUser('galleryIconBlock', $this->currentLanguage)) {
                $this->setGalleryIcons($attributeArray, $iceCatobject);
            }


            if (!$this->isFieldUpdatedByUser('reviews', $this->currentLanguage)) {
                $this->setReviewData($attributeArray['Reviews'], $iceCatobject);
            }

            if (!$this->isFieldUpdatedByUser('RelatedCategories')) {
                if ($this->config && (bool)$this->config->getCategorization() === true && isset($basicInformation['Category'])) {
                    $this->setCategories($basicInformation['Category'], $attributeArray, $iceCatobject);
                } else {
                    $iceCatobject->setRelatedCategories([]);
                }
            }
            if (!$this->isFieldUpdatedByUser('productRelated')) {
                $this->setProductRelated($iceCatobject, $attributeArray['ProductRelated']);
            }


        } catch (\Exception $e) {
            $this->csvLogMessage[] = 'ERROR IN FIX FIELD CREATION :' . $e->getMessage();

            $this->processingError[] = $e->getMessage();
            $this->logMessage = 'ERROR IN FIX FIELD FOR JOB ID :' . $this->jobId . 'AND PRODUCT ID :' . $this->currentProductId . '-' . $e->getMessage();
            $this->logger->addLog('create-object', $this->logMessage, $e->getTraceAsString(), 'ERROR');
        }
    }

    public function getFieldsModifiedLog($objectId, $lang='en')
    {
        $fieldsModifiedLogs = [];
        $icecatFieldLog = new \Pimcore\Model\DataObject\IcecatFieldsLog\Listing();
        //$icecatFieldLog->setCondition("pimcoreId = {$objectId} AND lang = '{$lang}'");
        $icecatFieldLog->setCondition("pimcoreId = {$objectId}");
        $list = (array) $icecatFieldLog->load();
        foreach ($list as $item) {
            $fieldName = $item->getName();
            $lang = $item->getLang();

            if (!empty($lang)) {
                $fieldsModifiedLogs[$lang][strtolower($fieldName)] = true;
            } else {
                $fieldsModifiedLogs[strtolower($fieldName)] = true;
            }
        }
        return $fieldsModifiedLogs;
    }

    public function isFieldUpdatedByUser($fieldName, $lang='')
    {
        $fieldsModifiedLogs = $this->modifiedFields;
        $fieldName = strtolower($fieldName);
        if (!empty($lang) && isset($fieldsModifiedLogs[$lang][$fieldName]) && $fieldsModifiedLogs[$lang][$fieldName]) {
            return true;
        } else if (isset($fieldsModifiedLogs[$fieldName]) && $fieldsModifiedLogs[$fieldName]) {
            return true;
        }
        return false;
    }

    protected function setReviewData($reviews, $object)
    {
        if (empty($reviews)) {
            return;
        }
        $blockData = [];
        foreach ($reviews as $data) {
            if (empty($data['Language'])) {
                $data['Language'] = 'en';
            }

            $data['Language'] = strtolower($data['Language']);
            $blockData[$data['Language']] = [];
            $blockData[$data['Language']][] = [
                    //'awardHighPic' => new BlockElement('awardHighPic', 'image', $this->getImageField($data, 'AwardHighPic')),
                    //'awardLogoPic' => new BlockElement('awardHighPic', 'image', $this->getImageField($data, 'AwardLogoPic')),
                    'bottomLine' => new BlockElement('bottomLine', 'input', $data['BottomLine'] ?? null),
                    'code' => new BlockElement('code', 'input', $data['Code'] ?? null),
                    'dateAdded' => new BlockElement('dateAdded', 'input', $data['DateAdded'] ?? null),
                    'group' => new BlockElement('group', 'input', $data['Group'] ?? null),
                    'reviewId' => new BlockElement('reviewId', 'input', $data['ID'] ?? null),
                    //'logoPic' => $new BlockElement('logoPic', 'image', this->getImageField($data, 'LogoPic')),
                    'Score' => new BlockElement('', 'input', $data['ID']),
                    'reviewValue' => new BlockElement('reviewValue', 'textarea', $data['Value'] ?? null),
                    'valueBad' => new BlockElement('valueBad', 'textarea', $data['ValueBad'] ?? null),
                    'valueGood' => new BlockElement('valueGood', 'textarea', $data['ValueGood'] ?? null),
//                    'icecatID' => new BlockElement('icecatID', 'input', $data['IcecatID']?? null),
                    'url' => new BlockElement('url', 'input', $data['URL'] ?? null),
//                    'updated' => new BlockElement('updated', 'input', $data['Updated'] ?? null),
                ];
        }

        foreach ($blockData as $language => $data) {

            $object->setReviews($data, $language);
        }
    }

    protected function setProductStoryData($ps, $object)
    {
        if (empty($ps)) {
            return;
        }
        $blockData = [];
        foreach ($ps as $data) {
            if (empty($data['Language'])) {
                $data['Language'] = 'en';
            }

            $data['Language'] = strtolower($data['Language']);
            $blockData[$data['Language']] = [];
            $blockData[$data['Language']][] = [
                'productStory' => new BlockElement('productStory', 'input', $data['URL'] ?? null)
            ];
        }
        foreach ($blockData as $language => $data) {

            $object->setProductStory($data, $language);
        }
    }

    /**
     * @param array $categoryInformation
     * @param \Pimcore\Model\DataObject\Icecat $iceCatobject
     *
     * @return void
     */
    protected function setCategories($categoryInformation, $attributeArray, $iceCatobject)
    {
        $categoryId = $categoryInformation['CategoryID'] ?? null;
        $categoryName = $categoryInformation['Name']['Value'] ?? null;
        $language = $categoryInformation['Name']['Language'] ?? null;

        if ($categoryId) {
            $categoryObject = \Pimcore\Model\DataObject\IcecatCategory::getByIcecat_id($categoryId, true);
            if (!$categoryObject) {
                $categoryObject = new \Pimcore\Model\DataObject\IcecatCategory();
                $categoryObject->setParent(\Pimcore\Model\DataObject\Service::createFolderByPath('/'.self::CAT_DATAOBJECT_FOLDER));
                $categoryObject->setKey($categoryId);
                $categoryObject->setIcecat_id($categoryId);
                $categoryObject->setPublished(true);
            }

            $categoryObject->setName($categoryName, strtolower($language));
            $data = [];
            if (!empty($attributeArray['FeaturesGroups'])) {
                $parentArray = $attributeArray['FeaturesGroups'];

                foreach ($parentArray as $featureGroup) {
                    if (!empty($featureGroup['Features'])) {
                        foreach ($featureGroup['Features'] as $features) {
                            if ($features['Searchable']) {
                                $data[] = [
                                    'type' => $features['Type'],
                                    'id' => $features['Feature']['ID'],
                                    'keyType' => $this->dataTypes[$features['Type']]
                                ];
                            }
                        }
                    }
                }
            }

            $categoryObject->setSearchableFeatures(\json_encode($data));
            $categoryObject->save();

            $iceCatobject->setRelatedCategories([$categoryObject]);
        }
    }

    /**
     * @param Icecat $object
     * @param $relatedProductsData
     */
    public function setProductRelated($object, $relatedProductsData)
    {

        if (empty($relatedProductsData)) {
            return;
        }
        $object->save();
        $products = [];
        $runTimeFetch = [];
        $counter = 0;
        foreach ($relatedProductsData as $data) {
            if (!empty($data['IcecatID'])) {
                $product = Icecat::getByIcecat_Product_Id($data['IcecatID'], null, 1);
                if (!empty($product)) {
                    $products[$data['IcecatID']] = $product;
                } else {
                    $runTimeFetch[] = $data;
                }
            }

            if ($counter++ > 40) {
                break;
            }
        }


        if (self::$recursiveFlag && (bool) $this->config->getImportRelatedProducts()) {
            self::$recursiveFlag = false;
            foreach ($runTimeFetch as $data) {
                $product = $this->createRelatedProduct($data);
                if (!empty($product)) {
                    $products[$data['IcecatID']] = $product;
                }
            }
        }
        if (!empty($products)) {
            $object->setProductRelated(array_unique($products));
        }
    }

    public function createRelatedProduct($data)
    {
        try {
            if (!empty($data['ProductCode']) && !empty($data['Brand'])) {
                $url = $this->importService->importUrl . "?UserName=" . $this->userId .
                    "&Language=" . $this->currentLanguage .
                    "&Brand=" . $data['Brand'] .
                    "&ProductCode=" . $data['ProductCode'];
                $completeData = json_decode($this->importService->fetchIceCatData($url), true);
                if (array_key_exists('statusCode', $completeData)) {
                } elseif (array_key_exists('COULD_NOT_RESOLVE_HOST', $completeData)) {
                } elseif (array_key_exists('msg', $completeData) && $completeData['msg'] == 'OK') {
                    $data = $completeData['data'];

                    $importData = [
                        'gtin' => $data['GeneralInfo']['IcecatId'],
                        'original_gtin' => !empty($data['GeneralInfo']['GTIN'][0]) ? $data['GeneralInfo']['GTIN'][0] : $data['GeneralInfo']['IcecatId'],
                        'language' => $this->currentLanguage,
                        'data_encoded' => base64_encode(json_encode($completeData))
                    ];
                    return $this->createIceCatObject($importData);
                }
            }
        } catch (\Exception $ex) {
            $this->logger->addLog('create-object', $ex->getMessage(), $ex->getTraceAsString(), 'ERROR');
        }
        return null;
    }

    public function setMultiMedia($attributeArray, $iceCatobject)
    {
        try {
            $this->logMessage = 'SETTING PDF DOCUMENTS  FOR JOB ID :' . $this->jobId . 'AND PRODUCT ID :' . $this->currentProductId;
            $this->logger->addLog('create-object', $this->logMessage, '', 'INFO');
            $multimediaArray = $attributeArray['Multimedia'];

            $multimediaNewArray = array_filter($multimediaArray, function ($var) {
                if (isset($var['ContentType']) && ($var['ContentType'] == 'application/pdf' || $var['ContentType'] == 'image/jpeg')) {
                    return true;
                } else {
                    return false;
                }
            });

            // saving assets
            $counter = 0;
            $assetArray = [];

            foreach ($multimediaNewArray as $media) {
                $link = $media['URL'];
                // Setting file name from link
                try {
                    $name = pathinfo($media['URL'], PATHINFO_FILENAME);
                    $extension = pathinfo($media['URL'], PATHINFO_EXTENSION);
                    $fileName = $name . '.' . $extension;
                } catch (\Exception $e) {
                    $fileName = uniqid();
                }
                // Setting assets from link
                $asset = \Pimcore\Model\Asset::getByPath('/' . self::ASSET_FOLDER . "/$fileName");
                if (!empty($asset)) {
                    $asset->setData(file_get_contents($link));
                    $asset->save();
                    $assetArray[$counter]['object'] = $asset;
                    $assetArray[$counter]['description'] = $media['Description'];
                    $assetArray[$counter]['contentType'] = $media['ContentType'];
                } else {
                    $newAsset = new \Pimcore\Model\Asset();
                    $newAsset->setFilename("$fileName");
                    $newAsset->setData(file_get_contents($link));
                    $newAsset->setParent(\Pimcore\Model\Asset::getByPath('/' . self::ASSET_FOLDER));
                    $newAsset->save();

                    $assetArray[$counter]['object'] = $newAsset;
                    $assetArray[$counter]['description'] = $media['Description'];
                    $assetArray[$counter]['contentType'] = $media['ContentType'];
                }

                $counter++;
            }
            //Setting relation with meta data

            $objectArray = [];
            $counter = 0;
            foreach ($assetArray as $mediaObject) {
                $objectMetadata = new \Pimcore\Model\DataObject\Data\ElementMetadata(
                    'multiMedia',
                    ['description', 'contentType'],
                    $mediaObject['object']
                );
                $objectMetadata->setDescription($mediaObject['description']);
                $objectMetadata->setContentType($mediaObject['contentType']);
                $objectArray[] = $objectMetadata;

                $counter++;
            }
            $iceCatobject->setMultiMedia($objectArray, $this->currentLanguage);
        } catch (\Exception $e) {
            $this->logMessage = 'ERROR IN PDF FIELD CREATION  FOR JOB ID :' . $this->jobId . 'AND PRODUCT ID :' . $this->currentProductId . '-' . $e->getMessage();
            $this->logger->addLog('create-object', $this->logMessage, $e->getTraceAsString(), 'ERROR');
        }
    }

    public function setGalleryIcons($attributeArray, $iceCatobject)
    {
        try {
            $this->logMessage = 'SETTING GALLERY ICONS  FOR JOB ID :' . $this->jobId . 'AND PRODUCT ID :' . $this->currentProductId;
            $this->logger->addLog('create-object', $this->logMessage, '', 'INFO');

            $featuresIconMasterArray = $attributeArray['FeatureLogos'];
            if (!empty($featuresIconMasterArray)) :
                $this->logger->addLog('create-object', $this->logMessage, '', 'INFO');

                $array = [];
                foreach ($featuresIconMasterArray as $featureIconArray) {
                    $iconUrl = $featureIconArray['LogoPic'];
                    $iconToolTipData = (isset($featureIconArray['Description'])) ? $featureIconArray['Description']['Value'] : '';
                    $data = \Pimcore\Tool::getHttpData($iconUrl);
                    $filename = basename($iconUrl);
                    $asset = \Pimcore\Model\Asset::getByPath('/' . self::ASSET_FOLDER . "/$filename");

                    if (!empty($asset)) {
                        $asset->setData($data);
                        $asset->save();
                    } else {
                        $asset = new \Pimcore\Model\Asset\Image();
                        $asset->setFilename("$filename");
                        $asset->setData($data);
                        $asset->setParent(\Pimcore\Model\Asset::getByPath('/' . self::ASSET_FOLDER));
                        $asset->save();
                    }
                    $data = [
                        'galleryIcon' => new BlockElement('galleryIcon', 'image', $asset),
                        'galleryIconValue' => new BlockElement('galleryIconValue', 'input', ($featureIconArray['Value'] ?? null)),
                        'galleryIconDescription' => new BlockElement('galleryIconDescription', 'textArea', $iconToolTipData),

                    ];

                    $array[] = $data;
                }
                $iceCatobject->setgalleryIconBlock($array, $this->currentLanguage);
            endif;
        } catch (\Exception $th) {
            $this->logMessage = 'ERROR IN GALLERY_ICON FIELD CREATION  FOR JOB ID :' . $this->jobId . 'AND PRODUCT ID :' . $this->currentProductId . '-' . $th->getMessage();
            $this->logger->addLog('create-object', $this->logMessage, [$th->getTraceAsString()], 'ERROR');
        }
    }

    public function setStoryField($attributeArray, $iceCatobject)
    {
        try {
            $this->logMessage = 'SETTING STORY URL JOB ID :' . $this->jobId . 'AND PRODUCT ID :' . $this->currentProductId;
            $this->logger->addLog('create-object', $this->logMessage, '', 'INFO');
            if (isset($attributeArray['ProductStory'][0])) :
                $storyUrl = $attributeArray['ProductStory'][0]['URL'];
                $iceCatobject->setStoryUrl($storyUrl, $this->currentLanguage);
            endif;
        } catch (\Exception $e) {
            $this->logMessage = 'ERROR IN STORY FIELD CREATION  FOR JOB ID :' . $this->jobId . 'AND PRODUCT ID :' . $this->currentProductId . '-' . $e->getMessage();
            $this->logger->addLog('create-object', $this->logMessage, $e->getTraceAsString(), 'ERROR');
        }
    }

    public function createBrandLogo($basicInformation, $iceCatobject)
    {
        try {
            if (isset($basicInformation['BrandLogo'])) :
                $this->logMessage = 'SETTING BRAND LOGO  FOR JOB ID :' . $this->jobId . 'AND PRODUCT ID :' . $this->currentProductId;
                $this->logger->addLog('create-object', $this->logMessage, '', 'INFO');

                $brandLogoUrl = $basicInformation['BrandLogo'];
                try {
                    $name = pathinfo($brandLogoUrl, PATHINFO_FILENAME);
                    $extension = pathinfo($brandLogoUrl, PATHINFO_EXTENSION);
                    $fileName = $name . '.' . $extension;
                } catch (\Exception $e) {
                    $fileName = uniqid();
                }

                // Setting assets from link
                $asset = \Pimcore\Model\Asset::getByPath('/' . self::ASSET_FOLDER . "/$fileName");
                if (!empty($asset)) {
                    $asset->setData(file_get_contents($brandLogoUrl));
                    $asset->save();
                    $asset = $asset;
                } else {
                    $newAsset = new \Pimcore\Model\Asset\Image();
                    $newAsset->setFilename("$fileName");
                    $newAsset->setData(file_get_contents($brandLogoUrl));
                    $newAsset->setParent(\Pimcore\Model\Asset::getByPath('/' . self::ASSET_FOLDER));
                    $newAsset->save();
                    $asset = $newAsset;
                }
                $iceCatobject->setBrandLogo($asset);
            endif;
        } catch (\Exception $e) {
            $this->processingError[] = $e->getMessage();
            $this->logMessage = 'ERROR IN SETTING BRAND LOGO  FOR JOB ID :' . $this->jobId . 'AND PRODUCT ID :' . $this->currentProductId . '-' . $e->getMessage();
            $this->logger->addLog('create-object', $this->logMessage, $e->getTraceAsString(), 'ERROR');
        }
    }

    /**
     * @param $data
     * @param $fieldName
     * @return Asset|Asset\Image|null
     */
    public function getImageField($data, $fieldName)
    {
        try {
            if (empty($data[$fieldName])) {
                return null;
            }
            $this->logMessage = 'SETTING ' . $fieldName .'  FOR JOB ID :' . $this->jobId . 'AND PRODUCT ID :' . $this->currentProductId;
            $this->logger->addLog('create-object', $this->logMessage, '', 'INFO');

            $fieldUrl = $data[$fieldName];
            try {
                $name = pathinfo($fieldUrl, PATHINFO_FILENAME);
                $extension = pathinfo($fieldUrl, PATHINFO_EXTENSION);
                $fileName = $name . '.' . $extension;
            } catch (\Exception $e) {
                $fileName = uniqid();
            }

            // Setting assets from link
            $asset = \Pimcore\Model\Asset::getByPath('/' . self::ASSET_FOLDER . "/$fileName");
            if (!empty($asset)) {
                $asset->setData(file_get_contents($fieldUrl));
                $asset->save();
            } else {
                $asset = new \Pimcore\Model\Asset\Image();
                $asset->setFilename("$fileName");
                $asset->setData(file_get_contents($fieldUrl));
                $asset->setParent(\Pimcore\Model\Asset::getByPath('/' . self::ASSET_FOLDER));
                $asset->save();
            }
            return $asset;
        } catch (\Exception $e) {
            $this->processingError[] = $e->getMessage();
            $this->logMessage = 'ERROR IN SETTING  ' . $fieldName .  '  FOR JOB ID :' . $this->jobId . 'AND PRODUCT ID :' . $this->currentProductId . '-' . $e->getMessage();
            $this->logger->addLog('create-object', $this->logMessage, $e->getTraceAsString(), 'ERROR');
            return null;
        }
    }

    public function createReasonsToBuy($attributeArray, $iceCatobject)
    {
        try {
            $flag = 'LEFT';
            if (isset($attributeArray['ReasonsToBuy'])) {
                $reasonsHtml = '<div class="col-md-12">';
                foreach ($attributeArray['ReasonsToBuy'] as $reasons) {
                    $reasonsHtml .= '<div class = "row" >';
                    if (isset($reasons['HighPic']) && (!empty($reasons['HighPic']))) :
                        if ($flag === 'LEFT') :
                            $reasonsHtml .= ' <div class = "col-md-8 col-sm-8 col-xs-8"> <h5><b>' . $reasons['Title'] . '</b></h5>';
                            $reasonsHtml .= '<span>' . $reasons['Value'] . '</span></div>';
                            $reasonsHtml .= ' <div class="col-md-4 col-sm-4 col-xs-4 "><img class = "image-left"  alt="IMAGE-NOT-AVAILABLE" src="' . $reasons['HighPic'] . '" /></div>';
                            $flag = 'RIGHT';
                        else :
                                    $reasonsHtml .= '<div class = "col-md-4 col-sm-4 col-xs-4 ">  <img class = "image-right"  alt="IMAGE-NOT-AVAILABLE" src="' . $reasons['HighPic'] . '" /> </div>';
                            $reasonsHtml .= '<div class="col-md-8 col-sm-8 col-xs-8 "><h5><b>' . $reasons['Title'] . '</b></h5>';
                            $reasonsHtml .= '<span>' . $reasons['Value'] . '</span></div>';

                            $flag = 'LEFT';
                        endif;
                    else :
                        $reasonsHtml .= '<div class = "col-md-12"> <h5><b>' . $reasons['Title'] . '</b></h5>';
                        $reasonsHtml .= '<span>' . $reasons['Value'] . '</span></div>';
                    endif;
                    $reasonsHtml .= '</div>';
                }
                $reasonsHtml .= '</div>';
                $iceCatobject->setReasons_to_buy($reasonsHtml, $this->currentLanguage);
            }
        } catch (\Exception $e) {
        }
    }

    public function createVideoField($multimediaArray, $iceCatobject)
    {
        try {
            if (!empty($multimediaArray)) {
                $this->logMessage = 'STARTING VIDEO FIELD CREATION FOR JOB ID :' . $this->jobId . 'AND PRODUCT ID :' . $this->currentProductId;
                $this->logger->addLog('create-object', $this->logMessage, '', 'INFO');

                //Filtering array which having video field
                $arrayHavingVideo = array_filter($multimediaArray, function ($var) {
                    if (isset($var['IsVideo']) && $var['IsVideo'] == 1) {
                        return true;
                    } else {
                        return false;
                    }
                });

                $arrayHavingVideo = array_values($arrayHavingVideo);

                //Taking only one array for now
                if (!empty($arrayHavingVideo)) {
                    $videosArr = [];
                    foreach ($arrayHavingVideo as $videos) {
//                    $videos = $arrayHavingVideo[0];

                        $videoLink = $videos['URL'];

                        // Getting Name from url
                        try {
                            $name = pathinfo($videoLink, PATHINFO_FILENAME);
                            $extension = pathinfo($videoLink, PATHINFO_EXTENSION);
                            $fileName = $name . '.' . $extension;
                        } catch (\Exception $e) {
                            $fileName = uniqid();
                        }

                        // Setting assets from link
                        $asset = \Pimcore\Model\Asset::getByPath('/' . self::ASSET_FOLDER . "/$fileName");
                        if (empty($asset)) {
                            //Saving video in asset folder
                            try {
                                $newAsset = new \Pimcore\Model\Asset();
                                $newAsset->setFilename(uniqid() . '.' . $extension);
                                $newAsset->setData(file_get_contents($videos['URL']));
                                $newAsset->setParent(\Pimcore\Model\Asset::getByPath('/' . self::ASSET_FOLDER));
                                $newAsset->save();
                                $videosArr[] = $newAsset;
                            } catch (\Exception $ex) {
                                $this->csvLogMessage[] = 'ERROR IN VIDEO creation :' . $ex->getMessage();
                            }
                        }
                    }
                    if (!empty($videosArr)) {
                        $iceCatobject->setVideos($videosArr);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->processingError[] = $e->getMessage();
            $this->csvLogMessage[] = 'ERROR IN VIDEO FIELD CREATION :' . $e->getMessage();

            $this->logMessage = 'ERROR VIDEO FIELD FOR JOB ID :' . $this->jobId . 'AND PRODUCT ID :' . $this->currentProductId . '-' . $e->getMessage();
            $this->logger->addLog('create-object', $this->logMessage, $e->getTraceAsString(), 'ERROR');
        }
    }

    public function create3dTourField($multimediaArray, $iceCatobject)
    {
        try {
            if (!empty($multimediaArray)) {
                $this->logMessage = 'STARTING TOUR FIELD CREATION FOR JOB ID :' . $this->jobId . 'AND PRODUCT ID :' . $this->currentProductId;
                $this->logger->addLog('create-object', $this->logMessage, '', 'INFO');

                //Filtering array which have 3d Tour field
                $array3dTour = array_filter($multimediaArray, function ($var) {
                    if (isset($var['3DTour'])) {
                        return true;
                    } else {
                        return false;
                    }
                });

                //Taking only one array for now
                if (isset($array3dTour[0]['3DTour'])) {
                    $images3d = $array3dTour[0]['3DTour'];

                    $assetArray = [];
                    foreach ($images3d as $images) {
                        $link = $images['Link'];
                        // Setting file name from link
                        // Ex: https://images.icecat.biz/img/360_original/raw/36972460_57b597c9-1c16-4390-bfd1-e3fd28b08583_0.jpg
                        try {
                            $name = pathinfo($images['Link'], PATHINFO_FILENAME);
                            $extension = pathinfo($images['Link'], PATHINFO_EXTENSION);
                            $fileName = $name . '.' . $extension;
                        } catch (\Exception $e) {
                            $fileName = uniqid();
                        }
                        // Setting assets from link
                        $asset = \Pimcore\Model\Asset::getByPath('/' . self::ASSET_FOLDER . "/$fileName");
                        if (!empty($asset)) {
                            $asset->setData(file_get_contents($link));
                            $asset->save();
                            $assetArray[] = $asset;
                        } else {
                            $newAsset = new \Pimcore\Model\Asset\Image();
                            $newAsset->setFilename("$fileName");
                            $newAsset->setData(file_get_contents($link));
                            $newAsset->setParent(\Pimcore\Model\Asset::getByPath('/' . self::ASSET_FOLDER));
                            $newAsset->save();
                            $assetArray[] = $newAsset;
                        }
                    }
                    // Setting images for Tour (Tour)
                    if (!empty($assetArray)) {
                        $items = [];
                        foreach ($assetArray as $img) {
                            $advancedImage = new \Pimcore\Model\DataObject\Data\Hotspotimage();
                            $advancedImage->setImage($img);
                            $items[] = $advancedImage;
                        }

                        $iceCatobject->setTour(new \Pimcore\Model\DataObject\Data\ImageGallery($items));
                    }
                }
            }
        } catch (\Exception $e) {
            $this->csvLogMessage[] = 'ERROR IN 3D TOUR FIELD CREATION : ' . $e->getMessage();

            $this->processingError[] = $e->getMessage();
            $this->logMessage = 'ERROR TOUR FIELD FOR JOB ID :' . $this->jobId . 'AND PRODUCT ID :' . $this->currentProductId . '-' . $e->getMessage();
            $this->logger->addLog('create-object', $this->logMessage, $e->getTraceAsString(), 'ERROR');
        }
    }

    public function createGallery($attributeArray, $iceCatobject)
    {
        try {
            $this->logMessage = 'STARTING GALLERY CREATION FOR JOB ID :' . $this->jobId . 'AND PRODUCT ID :' . $this->currentProductId;
            $this->logger->addLog('create-object', $this->logMessage, '', 'INFO');

            $galleryImagesParent = $attributeArray['Gallery'];
            $assetArray = [];
            foreach ($galleryImagesParent as $galleryImages) {
                $fileName = $galleryImages['ID'];
                $imageUrl = $galleryImages['Pic'];

                $asset = \Pimcore\Model\Asset::getByPath('/' . self::ASSET_FOLDER . "/$fileName");

                if (!empty($asset)) {
                    $asset->setData(file_get_contents($imageUrl));
                    $asset->save();
                    $assetArray[] = $asset;
                } else {
                    $newAsset = new \Pimcore\Model\Asset\Image();
                    $newAsset->setFilename("$fileName");
                    $newAsset->setData(file_get_contents($imageUrl));
                    $newAsset->setParent(\Pimcore\Model\Asset::getByPath('/' . self::ASSET_FOLDER));
                    $newAsset->save();
                    $assetArray[] = $newAsset;
                }
            }
            if (!empty($assetArray)) {
                $items = [];
                foreach ($assetArray as $img) {
                    $advancedImage = new \Pimcore\Model\DataObject\Data\Hotspotimage();
                    $advancedImage->setImage($img);
                    $items[] = $advancedImage;
                }

                $iceCatobject->setGallery(new \Pimcore\Model\DataObject\Data\ImageGallery($items));
            }
            $this->logMessage = 'COMPLETED GALLERY CREATION FOR JOB ID :' . $this->jobId . 'AND PRODUCT ID :' . $this->currentProductId;
            $this->logger->addLog('create-object', $this->logMessage, '', 'INFO');
        } catch (\Exception $e) {
            $this->processingError[] = $e->getMessage();
            $this->logMessage = 'ERROR IN GALLERY CREATION FOR JOB ID :' . $this->jobId . 'AND PRODUCT ID :' . $this->currentProductId . '-' . $e->getMessage();
            $this->logger->addLog('create-object', $this->logMessage, $e->getTraceAsString(), 'ERROR');
        }
    }

    public function createDynamicFields($attributeArray, $iceCatobject)
    {
        try {
            $this->logMessage = 'STARTING DYNAMIC FIELDS CREATION FOR JOB ID :' . $this->jobId . 'AND PRODUCT ID :' . $this->currentProductId;
            $this->logger->addLog('create-object', $this->logMessage, '', 'INFO');

            if (!empty($attributeArray['FeaturesGroups'])) {
                $parentArray = $attributeArray['FeaturesGroups'];

                foreach ($parentArray as $featureGroup) {
                    // Setting current feature group category , needs to empty it each time
                    $this->currentFeatureGroup = [];
                    $this->currentFeatureGroup[$this->currentLanguage] = $featureGroup['FeatureGroup']['Name']['Value'];
                    if (!empty($featureGroup['Features'])) {
                        foreach ($featureGroup['Features'] as $features) {
                            $type = $features['Type'];
                            //$keyName =  $features['Feature']['Name']['Value'];
                            $keyName = $features['Feature']['ID'];
                            $sign = $features['Feature']['Measure']['Signs']['_'];

                            // find key
                            try {
                                $dataType = $this->dataTypes[$type];

                                if ($dataType == 'QuantityValue' && empty($sign)) {
                                    $dataType = 'Input';
                                }
                                if ($dataType == 'InputQuantityValue' && empty($sign)) {
                                    $dataType == 'Numeric';
                                }
                            } catch (\Exception $e) {
                                $this->logMessage = 'SKIPING FIELDS  DYNAMIC FIELDS CREATIONFOR JOB ID :' . $this->jobId . 'AND PRODUCT ID :' . $this->currentProductId . '-' . $e->getMessage();
                                $this->logger->addLog('create-object', $this->logMessage, "FIELD TYPE : $type", 'ERROR');
                                continue;
                            }

                            $keyOb = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName($keyName . $dataType, $this->storeId);
                            if (empty($keyOb)) {
                                $this->createStoreKey($features);
                            } else {
                                $this->updateStoreKey($features);
                            }
                            // new \Pimcore\Model\DataObject\Data\QuantityValue(13, 1);
                            $store = $iceCatobject->getFeatures();
                            $groups = $store->getActiveGroups();
                            $iceCatobject->getFeatures()->setActiveGroups($groups + [$this->groupId => true]);
                            $iceCatobject->getFeatures()->setLocalizedKeyValue($this->groupId, $this->keyId, $this->valueToBeInsert, $this->currentLanguage);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->csvLogMessage[] = 'ERROR IN DYNAMICE FIELD CREATION :' . $e->getMessage();
            $this->processingError[] = $e->getMessage();
            $this->logMessage = 'ERROR IN DYNAMIC FIELDS CREATIONFOR JOB ID :' . $this->jobId . 'AND PRODUCT ID :' . $this->currentProductId . '-' . $e->getMessage();
            $this->logger->addLog('create-object', $this->logMessage, $e->getTraceAsString(), 'ERROR');
        }
    }

    public function createStoreKey($features)
    {
        try {
            $this->logMessage = 'STARTING CLASSFICATION STORE KEY CREATION FOR JOB ID :' . $this->jobId . 'AND PRODUCT ID :' . $this->currentProductId;
            $this->logger->addLog('create-object', $this->logMessage, '', 'INFO');

            $type = $features['Type'];
            //  $keyName =  $features['Feature']['Name']['Value'];
            $keyName = $features['Feature']['ID'];
            $title = $features['Feature']['Name']['Value'];
            $sign = $features['Feature']['Measure']['Signs']['_'];

            $dataType = $this->dataTypes[$type];

            if ($dataType == 'QuantityValue' && empty($sign)) {
                $dataType = 'Input';
            }
            if ($dataType == 'InputQuantityValue' && empty($sign)) {
                $dataType == 'Numeric';
            }

            $className = "\Pimcore\Model\DataObject\ClassDefinition\Data" . '\\' . $dataType;
            $definition = new $className();
            $definition->setName($keyName);
            $definition->setTitle($title);
            $value = '';
            switch ($dataType) :
                case 'QuantityValue':
                    $tempValue = $features['RawValue'];
                    $signid = $this->processQuantityValueUnit($sign);
                    $value = new \Pimcore\Model\DataObject\Data\QuantityValue($tempValue, $signid);
                    break;
                case 'Select':
                    $value = $features['Value'];
                    $optionSetterArray = [['key' => "$value", 'value' => "$value"]];
                    $definition->setOptions($optionSetterArray);
                    break;
                case 'Textarea':
                    $value = $features['Value'];
                    break;
                case 'InputQuantityValue':
                    $tempValue = $features['RawValue'];
                    $signid = $this->processQuantityValueUnit($sign);
                    $value = new \Pimcore\Model\DataObject\Data\InputQuantityValue($tempValue, $signid);
                    break;
                case 'Multiselect':
                    $value = $features['Value'];
                    $value = (explode(',', $value));
                    foreach ($value as $key) {
                        $optionSetterArray[] = ['key' => "$key", 'value' => "$key"];
                    }

                    $definition->setOptions($optionSetterArray);
                    break;
                case 'BooleanSelect':
                    $value = $features['Value'];
                    $definition->setNoLabel('NO');
                    $definition->setYesLabel('YES');
                    $definition->setEmptyLabel('EMPTY');
                    $value = ($value == 'Y') ? true : false;

                    break;
                case 'Input':
                    $value = $features['Value'];
                    break;
            endswitch;
            //Creating collecton
            $collectionId = $this->createCollection($keyName, $dataType);
            //Creating Group
            $groupId = $this->createGroup($keyName, $dataType);
            //Adding group to  collection
            $this->setCollectionGroupLink($groupId, $collectionId);

            \Pimcore\Model\DataObject\Classificationstore\KeyConfig::setCacheEnabled(false);
            $keyConfig = new \Pimcore\Model\DataObject\Classificationstore\KeyConfig();
            $keyConfig->setName($keyName . $dataType);

            // set description

            $keyConfig->setDescription(serialize($this->currentFeatureGroup));
            $keyConfig->setEnabled(true);
            $keyConfig->setType($definition->getFieldtype());
            $keyConfig->setStoreId($this->storeId);
            $keyConfig->setDefinition(json_encode($definition)); // The definition is used in object editor to render fields
            $keyConfig->save();

            //set key - group relation
            $this->setGroupKeyLink($groupId, $keyConfig->getId());

            //setting properties
            $this->keyId = $keyConfig->getId();
            $this->valueToBeInsert = $value;
            $this->groupId = $groupId;
        } catch (\Exception $e) {
            $this->processingError[] = $e->getMessage();
            $this->logMessage = 'ERROR IN CLASSFICATION STORE KEY CREATION FOR JOB ID :' . $this->jobId . 'AND PRODUCT ID :' . $this->currentProductId . '-' . $e->getMessage();
            $this->logger->addLog('create-object', $this->logMessage, $e->getTraceAsString(), 'ERROR');
        }
    }

    public function updateStoreKey($features)
    {
        $this->logMessage = 'STARTING CLASSFICATION STORE KEY UPDATION FOR JOB ID :' . $this->jobId . 'AND PRODUCT ID :' . $this->currentProductId;
        $this->logger->addLog('create-object', $this->logMessage, '', 'INFO');

        try {
            $ob = new \Pimcore\Model\DataObject\ClassDefinition\Data\QuantityValue();

            $type = $features['Type'];
            // $keyName =  $features['Feature']['Name']['Value'];
            $keyName = $features['Feature']['ID'];
            $sign = $features['Feature']['Measure']['Signs']['_'];

            $dataType = $this->dataTypes[$type];
            if ($dataType == 'QuantityValue' && empty($sign)) {
                $dataType = 'Input';
            }

            if ($dataType == 'InputQuantityValue' && empty($sign)) {
                $dataType == 'Numeric';
            }

            $className = "\Pimcore\Model\DataObject\ClassDefinition\Data" . '\\' . $dataType;
            $definition = new $className();
            $definition->setName($keyName);

            \Pimcore\Model\DataObject\Classificationstore\KeyConfig::setCacheEnabled(false);
            $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName($keyName . $dataType, $this->storeId);

            $previousDefiniton = json_decode($keyConfig->getDefinition(), true);

            // Setting Title from previous Definition
            $title = $previousDefiniton['title'];
            $definition->setTitle($title);

            $value = '';
            switch ($dataType) :
                case 'QuantityValue':
                    $tempValue = $features['RawValue'];
                    $signid = $this->processQuantityValueUnit($sign);
                    $value = new \Pimcore\Model\DataObject\Data\QuantityValue($tempValue, $signid);
                    break;
                case 'Select':
                    $keyConfigDef = json_decode($keyConfig->getDefinition(), true);

                    $value = $features['Value'];
                    $previousOptions = $keyConfigDef['options'];

                    if (array_search($value, array_column($previousOptions, 'value')) === false) {
                        $previousOptions[] = ['key' => "$value", 'value' => "$value"];
                    }
                    $definition->setOptions($previousOptions);
                    break;
                case 'Textarea':
                    $value = $features['Value'];
                    break;
                case 'InputQuantityValue':
                    $tempValue = $features['RawValue'];
                    $signid = $this->processQuantityValueUnit($sign);
                    $value = new \Pimcore\Model\DataObject\Data\InputQuantityValue($tempValue, $signid);

                    break;
                case 'Multiselect':
                    $keyConfigDef = json_decode($keyConfig->getDefinition(), true);
                    $value = $features['Value'];
                    $value = (explode(',', $value));
                    $previousOptions = $keyConfigDef['options'];
                    foreach ($value as $key) {
                        if (array_search($value, array_column($previousOptions, 'value')) === false) {
                            $previousOptions[] = ['key' => "$key", 'value' => "$key"];
                        }
                    }

                    $definition->setOptions($previousOptions);

                    break;
                case 'BooleanSelect':
                    $value = $features['Value'];
                    $definition->setNoLabel('NO');
                    $definition->setYesLabel('YES');
                    $definition->setEmptyLabel('EMPTY');
                    $value = ($value == 'Y') ? true : false;

                    break;
                case 'Input':
                    $value = $features['Value'];
                    break;
            endswitch;

            // Updating key description because it store category according language
            $previousDescriptionSerailazedArray = $keyConfig->getDescription();
            $previousDescriptionArray = unserialize($previousDescriptionSerailazedArray);

            $keyConfig->setDescription(serialize(array_merge($previousDescriptionArray, $this->currentFeatureGroup)));
            $keyConfig->setDefinition(json_encode($definition)); // The definition is used in object editor to render fields
            $keyConfig->save();

            $previousDescriptionSerailazedArray = $keyConfig->getDescription();
            $previousDescriptionArray = unserialize($previousDescriptionSerailazedArray);

            //setting properties

            $this->keyId = $keyConfig->getId();
            $this->valueToBeInsert = $value;

            $groupObject = \Pimcore\Model\DataObject\Classificationstore\GroupConfig::getByName($keyName . $dataType . 'Group', $this->storeId);
            $this->groupId = $groupObject->getId();
        } catch (\Exception $e) {
            $this->processingError[] = $e->getMessage();
            $this->logMessage = 'ERROR IN CLASSFICATION STORE KEY UPDATION FOR JOB ID :' . $this->jobId . 'AND PRODUCT ID :' . $this->currentProductId . '-' . $e->getMessage();
            $this->logger->addLog('create-object', $this->logMessage, $e->getTraceAsString(), 'ERROR');
        }
    }

    public function processQuantityValueUnit($sign)
    {
        $getUnitType = \Pimcore\Model\DataObject\QuantityValue\Unit::getById($this->unitPrefix . $sign);
        if (empty($getUnitType)) {
            $unit = new \Pimcore\Model\DataObject\QuantityValue\Unit();
            $unit->setAbbreviation($sign);   // mandatory
            $unit->setLongname($sign);
            $unit->setId($this->unitPrefix . $sign);
            $unit->save();

            return   $unit->getId();
        } else {
            //do nothing;
            return $getUnitType->getId();
        }
    }

    public function createGroup($keyName, $dataType)
    {
        $name = $keyName . $dataType . 'Group';
        $groupConfig = new \Pimcore\Model\DataObject\Classificationstore\GroupConfig();
        $groupConfig->setName($name);
        //Description will be use for categorizing groups
        $groupConfig->setDescription($keyName);
        $groupConfig->setStoreId($this->storeId);
        $groupConfig->save();

        return $groupConfig->getId();
    }

    public function createCollection($keyName, $dataType)
    {
        $name = $keyName . $dataType . 'Collection';
        $collectionConfig = new \Pimcore\Model\DataObject\Classificationstore\CollectionConfig();
        $collectionConfig->setName($name);
        $collectionConfig->setDescription($keyName);
        $collectionConfig->setStoreId($this->storeId);
        $collectionConfig->save();

        return $collectionConfig->getId();
    }

    public function setGroupKeyLink($groupId, $keyId)
    {
        $rel = new \Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation();
        $rel->setGroupId($groupId);
        $rel->setKeyId($keyId);
        $rel->save();
    }

    public function setCollectionGroupLink($groupId, $collectionId)
    {
        $rel = new \Pimcore\Model\DataObject\Classificationstore\CollectionGroupRelation();
        $rel->setGroupId($groupId);
        $rel->setColId($collectionId);
        $rel->save();
    }
}
