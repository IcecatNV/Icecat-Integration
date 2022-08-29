<?php

namespace IceCatBundle\Controller;

use IceCatBundle\Command\RecurringImportCommand;
use IceCatBundle\InstallClass;
use IceCatBundle\Services\TransformationDataTypeService;
use IceCatBundle\Model\Configuration;
use IceCatBundle\Services\CreateObjectService;
use IceCatBundle\Services\DataService;
use IceCatBundle\Services\FileUploadService;
use IceCatBundle\Services\IceCatCsvLogger;
use IceCatBundle\Services\ImportService;
use IceCatBundle\Services\JobHandlerService;
use IceCatBundle\Services\SearchService;
use Pimcore\Controller\FrontendController;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Model\User;
use Pimcore\Tool;
use Pimcore\Model\DataObject\ClassDefinition;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends FrontendController
{
    const ICECAT_CLASS_IDS = [
        'Icecat',
        'icecat_category',
        'icecat_fields_log'
    ];

    const FIELDS_TYPE_ALLOWED_FOR_CRONTAB_CONFIG = [
        'input',
        'textarea',
        'select'
    ];
    /**
     * @var array
     */
    const SYSTEM_COLUMNS = ['oo_id', 'fullpath', 'key', 'published', 'creationDate', 'modificationDate', 'filename', 'classname'];

    /**
     * @var string
     */
    const ICE_CAT_FOLDER_NAME = '/ICECAT/';

    /**
     * @var string
     */
    const JOB_DATA_CONTAINER_TABLE = 'ice_cat_processes';

    /**
     * @Route("/admin/icecat/get-config", name="icecat_getconfig", options={"expose"=true})
     *
     * @param Request $request
     */
    public function getConfigAction(Request $request, SearchService $searchService)
    {
        try {
            $config = Configuration::load();

            if ($config) {
                return $this->json(
                    [
                        'success' => true,
                        'data' => [
                            'languages' => $config->getLanguages(),
                            'categorization' => $config->getCategorization(),
                            'importRelatedProducts' => $config->getImportRelatedProducts(),
                            'showSearchPanel' => $searchService->isSearchEnable(),
                            'searchLanguages' => $searchService->getSearchLanguages(),
                            'productClass' => $config->getProductClass(),
                            'gtinField' => [
                                'name' => $config->getGtinField(),
                                'type' => $config->getGtinFieldType(),
                                'referenceFieldName' => $config->getMappingGtinClassField(),
                                'language' => $config->getMappingGtinLanguageField(),
                            ],
                            'brandNameField' => [
                                'name' => $config->getBrandNameField(),
                                'type' => $config->getBrandNameFieldType(),
                                'referenceFieldName' => $config->getMappingBrandClassField(),
                                'language' => $config->getMappingBrandLanguageField(),
                            ],
                            'productNameField' => [
                                'name' => $config->getProductNameField(),
                                'type' => $config->getProductNameFieldType(),
                                'referenceFieldName' => $config->getMappingProductCodeClassField(),
                                'language' => $config->getMappingProductCodeLanguageField(),
                            ],
                            'cronExpression' => $config->getCronExpression(),
                            'assetFilePath' => $config->getAssetFilePath(),
                            'onlyNewObjectProcessed' => $config->getOnlyNewObjectProcessed(),
                            'lastImportSummary' => $this->getLastRunImportSummary()
                        ]
                    ]
                );
            } else {
                return $this->json(
                    [
                        'success' => true,
                        'data' => [
                            'languages' => ['en'],
                            'categorization' => false,
                            'showSearchPanel' => $searchService->isSearchEnable(),
                            'searchLanguages' => $searchService->getSearchLanguages(),
                            'productClass' => null,
                            'gtinField' => [
                                'name' => null,
                                'type' => null,
                                'referenceFieldName' => null,
                                'language' => null,
                            ],
                            'brandNameField' => [
                                'name' => null,
                                'type' => null,
                                'referenceFieldName' => null,
                                'language' => null,
                            ],
                            'productNameField' => [
                                'name' => null,
                                'type' => null,
                                'referenceFieldName' => null,
                                'language' => null,
                            ],
                            'cronExpression' => null,
                            'assetFilePath' => null,
                            'onlyNewObjectProcessed' => true,
                            'lastImportSummary' => $this->getLastRunImportSummary()
                        ]
                    ]
                );
            }


        } catch (\Exception $e) {
            return $this->json(
                [
                'success' => false,
                'message' => $e->getMessage()
                ]
            );
        }
    }

    /**
     * @Route("/admin/icecat/check-recurring-import-progress", name="icecat_check_recurring_import_progress", options={"expose"=true})
     *
     * @param Request $request
     */
    public function checkRecurringImportProcessAction()
    {
        $sql = "SELECT * FROM icecat_recurring_import WHERE status = 'running'";
        $result = \Pimcore\Db::get()->fetchAssociative($sql);
        if(!$result) {
            return new JsonResponse([
                'isRunning' => false,
                'totalItems' => 0,
                'processedItems' => 0,
                'progress' => 0
            ]);
        }

        $progress = $result['total_records'] > 0 ? round($result['processed_records'] / $result['total_records'], 2) : 1;

        return new JsonResponse([
            'isRunning' => true,
            'totalItems' => $result['total_records'],
            'processedItems' => $result['processed_records'],
            'progress' => $progress
        ]);
    }


    /**
     * @Route("/admin/icecat/get-last-run-import-summary", name="icecat_get_last_run_import_summary", options={"expose"=true})
     *
     * @param Request $request
     */
    public function getLastRunImportSummaryAction()
    {
        return new JsonResponse([
            'html' => $this->getLastRunImportSummary()
        ]);
    }

    /**
     * @return string
     */
    protected function getLastRunImportSummary()
    {
        $sql = "SELECT * FROM icecat_recurring_import WHERE status = 'finished'";
        $result = \Pimcore\Db::get()->fetchAssociative($sql);
        if(!$result) {
            $html = "<b>No summary found</b>";
            return $html;
        }

        $startDatetime = date("Y-m-d H:i A", $result['start_datetime']);
        $endDatetime = date("Y-m-d H:i A", $result['end_datetime']);
        $totalRecords = $result['total_records'];
        $successRecords = $result['success_records'];
        $errorRecords = $result['error_records'];
        $executionType = $result['execution_type'];

        $html = "
                <style>
                    table.summary {
                        border-collapse: collapse;
                        border-spacing: 0;
                        width: 100%;
                        border: 1px solid #ddd;
                    }

                    .summary th, .summary td {
                        text-align: left;
                        padding: 3px;
                    }

                    .summary tr:nth-child(even) {
                        background-color: #E0E1E2;
                    }
                </style>
                <table class=\"summary\">
                    <tr>
                        <th>Start Date & Time</th>
                        <td>{$startDatetime}</td>
                    </tr>
                    <tr>
                        <th>End Date & Time</th>
                        <td>{$endDatetime}</td>
                    </tr>
                    <tr>
                        <th>Total Records</th>
                        <td>{$totalRecords}</td>
                    </tr>
                    <tr>
                        <th>Success Records</th>
                        <td>{$successRecords}</td>
                    </tr>
                    <tr>
                        <th>Error Records</th>
                        <td>{$errorRecords}</td>
                    </tr>
                    <tr>
                        <th>Execution Type</th>
                        <td>{$executionType}</td>
                    </tr>
                    <tr>
                        <th>Detailed Log File</th>
                        <td><a href=\"/admin/icecat/download-last-import-log\">Download</a></td>
                    </tr>

                </table>";

        return $html;
    }

    /**
     * @Route("/admin/icecat/start-manual-import", name="icecat_start_manual_import", options={"expose"=true})
     *
     * @param Request $request
     */
    public function startManualImportAction()
    {
        $command = 'php ' . PIMCORE_PROJECT_ROOT . '/bin/console icecat:recurring-import --execution-type=manual';
        exec($command . ' > /dev/null &');
        sleep(2);
        return $this->json(
            [
                'success' => true,
            ]
        );
    }

    /**
     * @Route("/admin/icecat/download-last-import-log", name="icecat_download_last_import_log", options={"expose"=true})
     *
     * @param Request $request
     */
    public function downloadLastImportLogAction()
    {
        $filename = RecurringImportCommand::LAST_IMPORT_LOG_FILENAME. '.log';
        $response = new BinaryFileResponse(PIMCORE_LOG_DIRECTORY . "/" . $filename);
        $response->headers->set('Content-Type', 'text/csv');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        return $response;
    }

    /**
     * @Route("/admin/icecat/get-selected-languages", name="icecat_getselectedlanguages", options={"expose"=true})
     *
     * @param Request $request
     */
    public function getSelectedLanguagesAction()
    {
        try {
            $config = Configuration::load();

            foreach($config->getLanguages() as $lang) {
                $data[] = [
                    'key' => $lang,
                    'value' => \Locale::getDisplayLanguage($lang)
                ];
            }
            return $this->json(
                [
                    'success' => true,
                    'data' => $data
                ]
            );
        } catch (\Exception $e) {
            return $this->json(
                [
                    'success' => false,
                    'message' => $e->getMessage()
                ]
            );
        }
    }

    /**
     * @Route("/admin/icecat/get-folder-ids", name="icecat_getfolderids", options={"expose"=true})
     *
     * @param Request $request
     */
    public function getFolderIdsAction(Request $request, SearchService $searchService)
    {
        try {
            $productFolder = DataObject\Folder::getByPath(InstallClass::PRODUCT_FOLDER_PATH);
            $productFolderId = ($productFolder) ? $productFolder->getId() : null;
            $categoryFolder = DataObject\Folder::getByPath(InstallClass::CATEGORY_FOLDER_PATH);
            $categoryFolderId = ($categoryFolder) ? $categoryFolder->getId() : null;

            return $this->json(
                [
                'success' => true,
                'data' => [
                    'productfolderid' => $productFolderId,
                    'categoryfolderid' => $categoryFolderId
                ]
                ]
            );
        } catch (\Exception $e) {
            return $this->json(
                [
                'success' => false,
                'message' => $e->getMessage()
                ]
            );
        }
    }

    /**
     * @Route("/admin/icecat/save-config", name="icecat_saveconfig", options={"expose"=true})
     *
     * @param Request $request
     */
    public function saveConfigAction(Request $request)
    {
        $languages = $request->get('languages', null);
        $categorization = $request->get('categorization', null);
        $importRelatedProducts = $request->get('importRelatedProducts', null);
        $productClass = $request->get('productClass', null);

        $gtinField = $request->get('gtinField', null);
        $gtinFieldType = $request->get('gtinFieldType', null);
        $mappingGtinClassField  = $request->get('mappingGtinClassField', null);
        $mappingGtinLanguageField  = $request->get('mappingGtinLanguageField', null);

        $brandNameField = $request->get('brandNameField', null);
        $brandNameFieldType = $request->get('brandNameFieldType', null);
        $mappingBrandClassField  = $request->get('mappingBrandClassField', null);
        $mappingBrandLanguageField  = $request->get('mappingBrandLanguageField', null);

        $productNameField = $request->get('productNameField', null);
        $productNameFieldType = $request->get('productNameFieldType', null);
        $mappingProductCodeClassField = $request->get('mappingProductCodeClassField', null);
        $mappingProductCodeLanguageField  = $request->get('mappingProductCodeLanguageField', null);

        $assetFilePath = $request->get('assetFilePath', null);
        $cronExpression = $request->get('cronExpression', null);
        $onlyNewObjectProcessed = (bool)$request->get('onlyNewObjectProcessed', true);

        try {
            $config = Configuration::load();
            if (!$config) {
                $config = new Configuration();
            }
            if ($languages !== null) {
                $config->setLanguages(array_filter(explode('|', $languages)));
            }
            if ($categorization !== null) {
                $config->setCategorization($categorization === 'true' ? true : false);
            }

            if ($importRelatedProducts !== null) {
                $config->setImportRelatedProducts($importRelatedProducts === 'true' ? true : false);
            }

            if ($productClass !== null) {
                $config->setProductClass($productClass);
            }

            if ($gtinField !== null) {
                $config->setGtinField($gtinField);
            }

            if ($gtinFieldType !== null) {
                $config->setGtinFieldType($gtinFieldType);
            }

            if ($mappingGtinClassField !== null) {
                $config->setMappingGtinClassField($mappingGtinClassField);
            }

            if ($mappingGtinLanguageField !== null) {
                $config->setMappingGtinLanguageField($mappingGtinLanguageField);
            }

            if ($brandNameField !== null) {
                $config->setBrandNameField($brandNameField);
            }

            if($brandNameFieldType !== null) {
                $config->setBrandNameFieldType($brandNameFieldType);
            }

            if ($mappingBrandClassField !== null) {
                $config->setMappingBrandClassField($mappingBrandClassField);
            }

            if ($mappingBrandLanguageField !== null) {
                $config->setMappingBrandLanguageField($mappingBrandLanguageField);
            }

            if ($productNameField !== null) {
                $config->setProductNameField($productNameField);
            }

            if($productNameFieldType !== null) {
                $config->setProductNameFieldType($productNameFieldType);
            }

            if ($mappingProductCodeClassField !== null) {
                $config->setMappingProductCodeClassField($mappingProductCodeClassField);
            }

            if ($mappingProductCodeLanguageField !== null) {
                $config->setMappingProductCodeLanguageField($mappingProductCodeLanguageField);
            }

            if ($assetFilePath !== null) {
                $config->setAssetFilePath($assetFilePath);
            }

            if ($cronExpression !== null) {
                $config->setCronExpression($cronExpression);
            }

            if ($onlyNewObjectProcessed !== null) {
                $config->setOnlyNewObjectProcessed($onlyNewObjectProcessed);
            }

            $config->save();

            return $this->json(
                [
                'success' => true,
                'message' => 'OK'
                ]
            );
        } catch (\Exception $e) {
            return $this->json(
                [
                'success' => false,
                'message' => $e->getMessage()
                ]
            );
        }
    }

    /**
     * @Route("/ice-cat-create")
     *
     * @param CreateObjectService $createOb
     */
    public function createAction(CreateObjectService $createOb)
    {
        try {
            $response = $createOb->createObject(2, '6136f27516bd3');
        } catch (\Exception $e) {
        }

        return $this->json(['success' => true]);
    }

    /**
     *
     * @Route("/admin/icecat/get-logfile", name="icecat_loggrid_data", options={"expose"=true})
     *
     * @return JsonResponse
     */
    public function getObjectCreationLogFiles(Request $request, IceCatCsvLogger $fetchfile)
    {
        $fileData = $fetchfile->getLogFilesDetail();

        return $this->json(['data' => $fileData]);
    }

    /**
     *
     * @Route("/admin/icecat/download-logfile/{name}", name="ice_cat_download_log", options={"expose"=true})
     *
     * @return JsonResponse
     */
    public function downloadLogFile(Request $request)
    {
        try {
            $path = PIMCORE_PRIVATE_VAR . '/log' . '/OBJECT-CREATE';
            $fileName = $request->get('name');
            $response = new BinaryFileResponse($path . '/' . $fileName . '.csv');
            $response->headers->set('Content-Type', 'text/csv');
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $fileName . '.csv');
            return $response;
        } catch (\Exception $e) {
            return $this->redirect($request->server->get('HTTP_REFERER'));
        }
    }

    /**
     *
     * @Route("/admin/icecat/delete-logfile/{name}", name="deletelogfile", options={"expose"=true})
     *
     * @return JsonResponse
     */
    public function deleteLogFile(Request $request)
    {
        try {
            $path = PIMCORE_PRIVATE_VAR . '/log' . '/OBJECT-CREATE';
            $fileName = $request->get('name');
            if (file_exists($path . '/' . $fileName . '.csv')) {
                unlink($path . '/' . $fileName . '.csv');
            }
            $response = $this->json(['status' => 'true']);

            return $response;
        } catch (\Exception $e) {
            return $this->redirect($request->server->get('HTTP_REFERER'));
        }
    }

    /**
     *
     * @Route("/admin/icecat/get-product-count/", name="icecat_check_product_count", options={"expose"=true})
     *
     * @return JsonResponse
     */
    public function countProduct(Request $request)
    {
        try {
            $objects = \Pimcore\Model\DataObject::getByPath('/ICECAT', true);
            if (!empty($objects)) {
                if (($objects->getChildAmount()) == 0) {
                    $response = ['status' => 'false', 'id' => "{$objects->getId()} ", 'count' => '0'];
                } else {
                    $response = ['status' => 'true', 'id' => "{$objects->getId()}", 'count' => "{$objects->getChildAmount()}"];
                }
            } else {
                $response = ['status' => 'false', 'id' => '-', 'count' => '0'];
            }

            return $this->json($response);
        } catch (\Exception $e) {
            $response = ['status' => 'false', 'count' => '0'];

            return $this->json($response);
        }
    }

    /**
     * @Route("/ice-cat")
     *
     * @param ImportService $import
     *
     * @return \Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse
     */
    public function indexAction(ImportService $import)
    {
        $command = 'php ' . PIMCORE_PROJECT_ROOT . '/bin/console icecat:import ' . '613b36cbd36cf';
        $response = $import->importData('613b36cbd36cf');

        return $response;
    }

    /**
     *
     *
     * @Route("/icecat/upload-by-url", name="icecat_upload-by-url", options={"expose"=true})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function uploadFileViaUrl(Request $request, FileUploadService $fileUploadService, JobHandlerService $jobHandler)
    {
        $fileurl = $request->get('url');
        //        $fileurl = 'http://frilec.fastfish.nl/gtin.csv';
        $languages = $request->request->get('language');
        //print_r($request->request->get('language'));
        // Use basename() function to return the base name of file
        $fileName = basename($fileurl);

        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        if ($fileExtension != 'csv' && $fileExtension != 'xlsx') {
            return  $this->json(['success' => 'false', 'status' => '303', 'message' => 'File must be either csv or excel!']);
        }
        $userId = 2; //$request->get('user');
        $iceCatUser = $request->get('iceCatUser');
        $user = User::getById($userId);
        $fileUploadService->saveFileViaUrl($fileurl, $user, $fileName);
        $jobId = $jobHandler->makeJobEntry($userId, $iceCatUser, $fileName, $fileExtension, $languages);
        $command = 'php ' . PIMCORE_PROJECT_ROOT . '/bin/console icecat:import ' . $jobId;
        try {
            exec($command . ' > /dev/null &');
            sleep(2);
            $otherInfo = $fileUploadService->getOtherInfo(true);
        } catch (\Exception $ex) {
            return $this->json(['success' => 'false', 'error' => true, 'status' => '200']);
        }

        return $this->json(['success' => 'true', 'status' => '200', 'otherInfo' => $otherInfo]);
    }

    /**
     * @Route("/icecat/upload-file", methods={"POST"}, name="icecat_upload-file", options={"expose"=true})
     *
     * @param Request           $request
     * @param FileUploadService $fileUploadService
     *
     * @return JsonResponse
     */
    public function uploadFile(Request $request, FileUploadService $fileUploadService, JobHandlerService $jobHandler)
    {
        $file = $request->files->get('File');
        $languages = implode('|', $request->request->get('language'));
        // $languages = 'en';
        $fileExtension = $file->getClientOriginalExtension();
        $fileName = $file->getClientOriginalName();
        // //file validation
        if ($fileExtension != 'csv' && $fileExtension != 'xlsx') {
            return  $this->json(['success' => 'false', 'status' => '303']);
        }
        $userId = $request->get('user');
        $iceCatUser = $request->get('iceCatUser');
        $user = User::getById($userId);
        $fileUploadService->saveFile($file, $user);
        $jobId = $jobHandler->makeJobEntry($userId, $iceCatUser, $fileName, $fileExtension, $languages);
        $command = 'php ' . PIMCORE_PROJECT_ROOT . '/bin/console icecat:import ' . $jobId;
        try {
            exec($command . ' > /dev/null &');
            sleep(2);
            $otherInfo = $fileUploadService->getOtherInfo(true);
        } catch (\Exception $ex) {
            return $this->json(['success' => 'false', 'error' => true, 'status' => '200']);
        }

        return $this->json(['success' => 'true', 'status' => '200', 'otherInfo' => $otherInfo]);
    }

    /**
     * @Route("/admin/icecat/get-progress-bar-data", methods={"GET"}, name="icecat_get-progress-bar-data", options={"expose"=true})
     *
     * @param Request     $request
     * @param DataService $dataService
     *
     * @return JsonResponse
     */
    public function getDataForProgressbar(Request $request, DataService $dataService)
    {
        $type = $request->get('type');

        $items = [];
        if ($type == 'fetching') {
            $items = $dataService->getDataForFetchingProgressbar();
        } elseif ($type == 'processing') {
            $items = $dataService->getDataForCreationProgressbar();
        }

        return $this->json(['items' => $items, 'success' => true, 'active' => count($items)]);
    }

    /**
     * @Route("/admin/icecat/grid-get-col-config", name="icecat_grid-get-col-config", options={"expose"=true})
     *
     * @param Request $request
     * @param bool    $isDelete
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function doGetGridColumnConfig(Request $request, $isDelete = false)
    {
        $deConfigId = 1;
        $classId = 1111;
        $availableFields = [];
        $gridConfig = $this->getColumnConfig($classId, $deConfigId);
        $gridSettings = []; //$this->getColumnSettings($deConfigId);
        $savedColumns = $gridConfig['columns'];

        foreach ($savedColumns as $key => $sc) {
            if (in_array($key, self::SYSTEM_COLUMNS)) {
                $colConfigSys = [
                    'key' => $key,
                    'type' => 'system',
                    'label' => $sc['fieldConfig']['label'],
                    'position' => $sc['position']
                ];
                if (isset($sc['width'])) {
                    $colConfigSys['width'] = $sc['width'];
                }

                $availableFields[] = $colConfigSys;
            } else {
                $colConfig = [];

                $colConfig['key'] = $key;
                $colConfig['type'] = $sc['fieldConfig']['type'];

                $inputtype = ['image', 'country', 'href', 'multihrefMetadata'];
                if (in_array($sc['fieldConfig']['type'], $inputtype)) {
                    $colConfig['type'] = 'input';
                }

                $colConfig['label'] = $sc['fieldConfig']['label'];
                $colConfig['config'] = ['width' => null];
                $colConfig['position'] = $sc['position'];
                $colConfig['width'] = $sc['fieldConfig']['width'];

                if (isset($sc['fieldConfig']['layout']['langCode'])) {
                    $colConfig['layout']['langCode'] = $sc['fieldConfig']['layout']['langCode'];
                }

                if (isset($sc['fieldConfig']['layout']['classId'])) {
                    $colConfig['layout']['classId'] = $sc['fieldConfig']['layout']['classId'];
                    $colConfig['layout']['classId'] = 20;
                }

                if (isset($sc['fieldConfig']['layout']['parentClassId'])) {
                    $colConfig['layout']['parentClassId'] = $sc['fieldConfig']['layout']['parentClassId'];
                }

                if (isset($sc['fieldConfig']['layout']['relPath'])) {
                    if (isset($sc['fieldConfig']['layout']['classes'][0]['classes'])) {
                        $className = $sc['fieldConfig']['layout']['classes'][0]['classes'];
                        $classArr = \Pimcore\Model\DataObject\ClassDefinition::getByName($className); //p_r($classArr);
                        $classId = $classArr->getId();
                        $colConfig['layout']['relPath'] = $classId;
                    } else {
                        $colConfig['layout']['relPath'] = $sc['fieldConfig']['layout']['relPath'];
                    }
                }

                if (isset($sc['fieldConfig']['layout']['ownertype'])) {
                    $colConfig['layout']['ownertype'] = $sc['fieldConfig']['layout']['ownertype'];
                }

                if (isset($sc['fieldConfig']['layout']['ownername'])) {
                    $colConfig['layout']['ownername'] = $sc['fieldConfig']['layout']['ownername'];
                }

                /*if (isset($sc['fieldConfig']['layout']['fcName'])) {
                    $colConfig['layout']['fcName'] = $sc['fieldConfig']['layout']['fcName'];
                }*/

                if (isset($sc['fieldConfig']['layout']['fcClassId'])) {
                    $colConfig['layout']['fcClassId'] = $sc['fieldConfig']['layout']['fcClassId'];
                }

                if (isset($sc['fieldConfig']['layout']['fieldname'])) {
                    $colConfig['layout']['fieldname'] = $sc['fieldConfig']['layout']['fieldname'];
                }

                if ($sc['fieldConfig']['type'] == 'href') {
                    //$colConfig['type'] = $sc['fieldConfig']['type'];
                    $colConfig['label'] = $sc['fieldConfig']['label'];
                    $colConfig['position'] = $sc['position'];
                    $colConfig['width'] = $sc['width'];
                    $colConfig['layout'] = $sc['fieldConfig']['layout'];
                    unset($colConfig['config']);
                    unset($colConfig['isOperator']);
                    unset($colConfig['attributes']);
                    //$className = $column['layout']['classes'][0]['classes'];
                    //$classArr = \Pimcore\Model\DataObject\ClassDefinition::getByName($className); //p_r($classArr);
                    //$classId = $classArr->getId();
                    //$this->selectFields[] = "`object_".$classId."`.`o_path` AS `".$column['key']."`";
                    //echo $column['layout']['classes'][0]['classes'];
                    //$this->selectFields[] = "`jt".;
                } elseif (array_key_exists('isOperator', $sc['fieldConfig'])) {
                    if ($sc['fieldConfig']['isOperator'] == true) {
                        $colConfig['type'] = $sc['fieldConfig']['attributes']['type'];
                        $colConfig['label'] = $sc['fieldConfig']['attributes']['label'];
                        $colConfig['position'] = $sc['position'];
                        $colConfig['width'] = $sc['width'];
                        $colConfig['isOperator'] = true;
                        $colConfig['attributes'] = $sc['fieldConfig']['attributes'];
                        $colConfig['layout'] = $sc['fieldConfig']['layout'];
                        unset($colConfig['config']);
                    }
                } else {
                    $colConfig['layout'] = $sc['fieldConfig']['layout'];
                }

                $availableFields[] = $colConfig;
                unset($colConfig['layout']['relPath']);
                unset($colConfig['layout']['classId']);
                unset($colConfig['layout']['parentClassId']);
                //unset($colConfig['layout']['objbrickName']);
                unset($colConfig['layout']['langCode']);
                unset($colConfig['layout']['fieldname']);
                unset($colConfig['layout']['ownertype']);
                unset($colConfig['layout']['ownername']);
                //unset($colConfig['layout']['fcName']);
                unset($colConfig['layout']['fcClassId']);
            }
        }

        usort(
            $availableFields,
            function ($a, $b) {
                if ($a['position'] == $b['position']) {
                    return 0;
                }

                return ($a['position'] < $b['position']) ? -1 : 1;
            }
        );

        if (!empty($gridConfig) && !empty($gridConfig['language'])) {
            $language = $gridConfig['language'];
        }

        $config = [
            'sortinfo' => isset($gridConfig['sortinfo']) ? $gridConfig['sortinfo'] : false,
            'language' => $language,
            'availableFields' => $availableFields,
            'settings' => $gridSettings,
            'pageSize' => isset($gridConfig['pageSize']) ? $gridConfig['pageSize'] : false,
        ];

        return $this->json($config);
    }

    public function getColumnConfig($classId, $deConfigId)
    {
        $basicConfig = "{\n   \"language\":\"en\",\n   \"sortinfo\":false,\n   \"classId\":\"\",\n   \"columns\":{\n      \"gtin\":{\n         \"name\":\"gtin\",\n         \"position\":1,\n         \"hidden\":false,\n         \"width\":250,\n         \"fieldConfig\":{\n            \"key\":\"gtin\",\n            \"label\":\"GTIN\",\n            \"type\":\"system\",\n            \"layout\":{\n               \"classId\":\"\"\n            },\n            \"width\":250\n         }\n      },\n      \"original_gtin\":{\n         \"name\":\"original_gtin\",\n         \"position\":2,\n         \"hidden\":false,\n         \"width\":250,\n         \"fieldConfig\":{\n            \"key\":\"original_gtin\",\n            \"label\":\"GTIN\",\n            \"type\":\"system\",\n            \"layout\":{\n               \"classId\":\"\"\n            },\n            \"width\":250\n         }\n      },\n      \"product_name\":{\n         \"name\":\"product_name\",\n         \"position\":3,\n         \"hidden\":false,\n         \"width\":500,\n         \"fieldConfig\":{\n            \"key\":\"product_name\",\n            \"label\":\"ProductName\",\n            \"type\":\"system\",\n            \"layout\":{\n               \"classId\":\"\"\n            },\n            \"width\":500\n         }\n      },\n      \"is_product_found\":{\n         \"name\":\"is_product_found\",\n         \"position\":4,\n         \"hidden\":false,\n         \"width\":100,\n         \"fieldConfig\":{\n            \"key\":\"is_product_found\",\n            \"label\":\"Found\",\n            \"type\":\"system\",\n            \"layout\":{\n               \"classId\":\"\"\n            },\n            \"width\":100\n         }\n      },\n      \"fetching_date\":{\n         \"name\":\"fetching_date\",\n         \"position\":5,\n         \"hidden\":false,\n         \"width\":200,\n         \"fieldConfig\":{\n            \"key\":\"fetching_date\",\n            \"label\":\"Fetching Date\",\n            \"type\":\"system\",\n            \"layout\":{\n               \"classId\":\"\"\n            },\n            \"width\":200\n         }\n      },\n      \"language\":{\n        \"name\":\"language\",\n        \"position\":6,\n        \"hidden\":false,\n        \"width\":200,\n        \"fieldConfig\":{\n           \"key\":\"language\",\n           \"label\":\"Language\",\n           \"type\":\"system\",\n           \"layout\":{\n              \"classId\":\"\"\n           },\n           \"width\":200\n        }\n     }\n   }\n}";

        //   $basicConfig = '{"language":"en","sortinfo":false,"classId":"","columns":{"gtin":{"name":"gtin","position":1,"hidden":false,"width":250,"fieldConfig":{"key":"gtin","label":"GTIN","type":"system","layout":{"classId":""},"width":250}},"product_name":{"name":"product_name","position":2,"hidden":false,"width":500,"fieldConfig":{"key":"product_name","label":"ProductName","type":"system","layout":{"classId":""},"width":500}},"is_product_found":{"name":"is_product_found","position":3,"hidden":false,"width":100,"fieldConfig":{"key":"is_product_found","label":"Found","type":"system","layout":{"classId":""},"width":100}},"fetching_date":{"name":"fetching_date","position":4,"hidden":false,"width":200,"fieldConfig":{"key":"fetching_date","label":"Fetching Date","type":"system","layout":{"classId":""},"width":200}}}}';
        $savedGridConfig = $basicConfig;

        return json_decode($savedGridConfig, true);
    }

    /**
     *
     * @Route("/admin/icecat/grid-proxy", name="icecat_grid-proxy", options={"expose"=true})
     *
     * @return JsonResponse
     */
    public function getFetchingGridData(Request $request, DataService $dataService)
    {
        $gtins = $request->get('gtins');
        $page = $request->get('page');
        $gtinsPage = $request->get('gtinsPage');
        if ($gtins) {
            $dataService->setNotToImportGtinsInSession($request, explode(',', $gtins), $gtinsPage);
        } else {
            $dataService->setNotToImportGtinsInSession($request, [], $gtinsPage);
        }

        $start = $request->get('start');
        $limit = $request->get('limit');

        $data = $dataService->getDataForImportGrid($start, $limit, $page);

        return $this->json(['data' => $data['data'], 'success' => true, 'total' => $data['total']]);
    }

    /**
     *
     * @Route("/admin/icecat/fetched-records", name="icecat_grid-total-fetched-records", options={"expose"=true})
     *
     * @return JsonResponse
     */
    public function getTotalFetchedRecords(DataService $dataService)
    {
        $result = $dataService->getFoundProductCountoShowInGrid();

        return $this->json(['count' => $result['total'], 'job' => $result['jobid']]);
    }

    /**
     *
     * @Route("/admin/language/valid-languages", name="icecat_valid_languages", options={"expose"=true})
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function getActiveLanguage()
    {
        $activatedLanguage = Tool::getValidLanguages();
        $supportedtLocale = Tool::getSupportedLocales();
        $defaultLanguage = Tool::getDefaultLanguage();

        $activatedLangWithDisplayValue = array_intersect_key($supportedtLocale, array_flip($activatedLanguage));
        $finalResult = [];
        $i = 0;
        foreach ($activatedLangWithDisplayValue as $key => $language) {
            if ($key == $defaultLanguage) {
                $defaultLanguageSetter['display_value'] = $language;
                $defaultLanguageSetter['key'] = $key;
            } else {
                $finalResult[$i]['display_value'] = $language;
                $finalResult[$i]['key'] = $key;
            }

            $i++;
        }
        array_unshift($finalResult, $defaultLanguageSetter);

        return new \Symfony\Component\HttpFoundation\JsonResponse(['data' => $finalResult]);
    }

    /**
     *
     * @Route("/admin/icecat/crontab/get_classes", name="icecat_get_classes", options={"expose"=true})
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function getClasses(Request $request)
    {
        $classList = new DataObject\ClassDefinition\Listing();
        $classesDef = $classList->load();
        $classesName = [];
        $i=1;
        $classesName[0]['display_value'] = '(Empty)';
        $classesName[0]['key'] = null;
        foreach ($classesDef as $index => $class) {
            if (in_array($class->getId(), self::ICECAT_CLASS_IDS)) {
                continue;
            }
            $classesName[$i]['display_value'] = $class->getName();
            $classesName[$i++]['key'] = $class->getId();
        }
        return new \Symfony\Component\HttpFoundation\JsonResponse(['data' => $classesName]);
    }

    /**
     *
     * @Route("/admin/icecat/crontab/get_class_fields", name="icecat_get_class_fields", options={"expose"=true})
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function getClassFields(Request $request, TransformationDataTypeService $transformationDataTypeService)
    {
        $classId =  $request->get('classId', null);
        if($classId == "" || $classId == -1) {
            return new JsonResponse([
                'attributes' => []
            ]);
        }

        $transformationTargetType = $request->get('transformation_result_type', [TransformationDataTypeService::DEFAULT_TYPE, TransformationDataTypeService::NUMERIC, TransformationDataTypeService::DATA_OBJECT]);

        return new JsonResponse([
            'attributes' => $transformationDataTypeService->getPimcoreDataTypes($classId, $transformationTargetType, false, false, false)
        ]);
    }

    /**
     *
     * @Route("/admin/icecat/crontab/get-reference-fields", name="icecat_getreferencefields", options={"expose"=true})
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function getReferenceClassFields(Request $request, TransformationDataTypeService $transformationDataTypeService)
    {
        $classId =  $request->get('class', null);
        if($classId == "" || $classId == -1) {
            return new JsonResponse([
                'attributes' => []
            ]);
        }

        $class = ClassDefinition::getById($classId);
        /** @var \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation $field */
        $field = $class->getFieldDefinition($request->get("field"));

        if(!$field) {
            return new JsonResponse([
                'attributes' => []
            ]);
        }

        if(!method_exists($field, "getClasses")) {
            return new JsonResponse([
                'attributes' => []
            ]);
        }

        $referenceClass = is_array($field->getClasses()) && isset($field->getClasses()[0]) ? $field->getClasses()[0] : null;

        if($referenceClass === null) {
            throw new \Exception("No class attached on field - {$field->getTitle()}");
        }

        $class = ClassDefinition::getByName($referenceClass["classes"]);
        $classId = $class->getId();

        $transformationTargetType = $request->get('transformation_result_type', [TransformationDataTypeService::DEFAULT_TYPE, TransformationDataTypeService::NUMERIC]);

        return new JsonResponse([
            'attributes' => $transformationDataTypeService->getPimcoreDataTypes($classId, $transformationTargetType, false, false, false)
        ]);
    }

    /**
     * @Route("/admin/icecat/create-object", name="icecat_create-object", options={"expose"=true})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function createObject(Request $request, DataService $dataService)
    {
        $runningJobId = $dataService->getRunningJobId();
        $jobId = $request->get('jobId');
        if (empty($runningJobId)) {
            return $this->json(['success' => 'false', 'error' => true, 'status' => '200', 'message' => 'No Running job!']);
        }

        $jobId = $runningJobId;

        if ($dataService->anyRunningProcessExist()) {
            if (!$dataService->checkIfFetchingDone($jobId)) {
                return $this->json(['success' => 'false', 'error' => true, 'status' => '200', 'message' => 'Records Fetching is in progress!']);
            }
        } else {
            return $this->json(['success' => 'false', 'error' => true, 'status' => '200', 'message' => 'No Running job!']);
        }

        $gtins = $request->get('gtins');
        $gtinsPage = $request->get('gtinsPage');
        if ($gtins) {
            $dataService->setNotToImportGtinsInSession($request, explode(',', $gtins), $gtinsPage);
        } else {
            $dataService->setNotToImportGtinsInSession($request, [], $gtinsPage);
        }
        $currentUserId = $dataService->getPimUserId();

        $res = $dataService->commitNotToImportRecords($jobId);
        if (is_array($res) && $res['error'] && $res['error'] == 'NoData') {
            return $this->json(['success' => 'false', 'error' => true, 'status' => '200', 'message' => 'No records selected to process!']);
        }

        $command = 'php ' . PIMCORE_PROJECT_ROOT . '/bin/console icecat:create-object ' . $currentUserId . ' ' . $jobId;
        try {
            exec($command . ' > /dev/null &');
            //            sleep(2);
            $otherInfo = $dataService->getOtherInfo(true);
        } catch (\Exception $ex) {
            return $this->json(['success' => 'false', 'error' => true, 'status' => '200', 'message' => $ex->getMessage()]);
        }

        return $this->json(['success' => 'true', 'status' => '200', 'otherInfo' => $otherInfo]);
    }

    /**
     * @Route("/admin/icecat/open-object-listing", name="icecat_open-object-listing", options={"expose"=true})
     */
    public function openObjectListing()
    {
        $folder = Folder::getByPath(self::ICE_CAT_FOLDER_NAME);
        if ($folder) {
            return $this->json(['success' => 'true', 'status' => '200', 'folderId' => $folder->getId()]);
        }

        return $this->json(['success' => 'false', 'status' => '200', 'folderId' => 0]);
    }

    /**
     * @Route("/admin/icecat/terminate-proccess", methods={"GET"}, name="icecat_terminate_process", options={"expose"=true})
     *
     * @param Request     $request
     * @param DataService $dataService
     *
     * @return JsonResponse
     */
    public function terminateProcess(DataService $dataService)
    {
        $db = \Pimcore\Db::get();
        $updateQuery = 'UPDATE ice_cat_processes SET COMPLETED = 1';
        $db->executeQuery($updateQuery);
        $response = $this->json(['status' => 'true']);

        return $response;
    }

    /**
     *
     * @Route("/admin/icecat/get-unfound-products", name="icecat_grid_get_unfound_products_info", options={"expose"=true})
     *
     * @return JsonResponse
     */
    public function getInfoForUnfoundProducts(Request $request, DataService $dataService)
    {
        $result = $dataService->getProductThatareNotFound();
        $productInfo = [];
        $i = 0;
        if ($result['product'] == 0) {
            return $this->json(['product' => '', 'job' => '']);
        }
        foreach ($result['product'] as $product) {
            if (!empty($product['data_encoded'])) {
                $icecatResponse = json_decode(base64_decode($product['data_encoded']), true);
                $productInfo[$i]['message'] = (isset($icecatResponse['message'])) ? $icecatResponse['message'] : '';
            } elseif (!empty($product['reason'])) {
                $productInfo[$i]['message'] = $product['reason'];
            } else {
                $productInfo[$i]['message'] = $product['error'];
            }

            $productInfo[$i]['rowNumber'] = $product['gtin'];
            $searchKey = unserialize($product['search_key']);
            $searchedBy = '';
            if (!empty($searchKey)) {
                if (array_key_exists('gtin', $searchKey)) {
                    $searchedBy = 'GTIN:' . $searchKey['gtin'];
                } elseif (array_key_exists('brandName', $searchKey) && array_key_exists('productCode', $searchKey)) {
                    $searchedBy = 'BRAND NAME :' . $searchKey['brandName'] . ',PRODUCT CODE :' . $searchKey['productCode'];
                }
            }
            $productInfo[$i]['searchKey'] = $searchedBy;
            $i++;
        }

        return $this->json(['product' => $productInfo, 'job' => $result['jobid']]);
    }

    /**
     *
     * @Route("/admin/icecat/get-categories", name="icecat_categories_list", options={"expose"=true})
     *
     * @return JsonResponse
     */
    public function getCategoriesAction(Request $request)
    {
        $lang = $request->get('language');
        if (!$lang) {
            return $this->json(['success' => true, 'data' => []]);
        }

        $listing = new \Pimcore\Model\DataObject\IcecatCategory\Listing();
        $listing->loadIdList();

        $data = [];
        foreach ($listing as $category) {
            if (trim($category->getName($lang)) != '') {
                $data[] = [
                    'id' => $category->getId(),
                    'icecatId' => $category->getIcecat_id(),
                    'name' => $category->getName($lang)
                ];
            }
        }

        return $this->json(['success' => true, 'data' => $data]);
    }

    /**
     *
     * @Route("/admin/icecat/get-brands", name="icecat_brands_list", options={"expose"=true})
     *
     * @return JsonResponse
     */
    public function getBrandsAction(Request $request, SearchService $searchService)
    {
        $lang = $request->get('language');
        if (!$lang) {
            return $this->json(['success' => true, 'data' => []]);
        }

        $brands = $searchService->getBrands($request);

        return $this->json(['success' => true, 'data' => $brands]);
    }

    /**
     * @Route("/admin/icecat/get-searchable-features", name="icecat_searchablefeatures_list", options={"expose"=true})
     *
     * @return JsonResponse
     */
    public function getSearchableFeaturesAction(Request $request, SearchService $searchService)
    {
        $categoryId = $request->get('categoryID', null);
        $language = $request->get('language', 'en');

        $category = \Pimcore\Model\DataObject\IcecatCategory::getById($categoryId);
        if (!$category) {
            return $this->json(['success' => false, 'message' => 'Invalid category']);
        }

        $searchableFeaturesList = \json_decode($category->getSearchableFeatures(), true);
        if (!is_array($searchableFeaturesList) || count($searchableFeaturesList) == 0) {
            return $this->json(['success' => true, 'data' => []]);
        }

        $featuresList = $stores = [];
        foreach ($searchableFeaturesList as $feature) {
            $CSKeyData = $searchService->getCSKeyForFeature($feature['id'], $feature['keyType']);
            if ($CSKeyData) {
                $featuresList[] = $CSKeyData;
                $featureValues = $searchService->getValuesForCSKey($request, $CSKeyData, $language);
                $stores[$CSKeyData['id']] = $featureValues;
            }
        }

        $data = [
            'featuresList' => $featuresList,
            'stores' => $stores,
        ];

        return $this->json(['success' => true, 'data' => $data]);
    }

    /**
     * @Route("/admin/icecat/get-search-results", name="icecat_searchresult", options={"expose"=true})
     *
     * @return JsonResponse
     */
    public function getSearchResultAction(Request $request, SearchService $searchService)
    {
        $data = $searchService->getSearchResultData($request);
        $totalCount = $searchService->getSearchResultCount($request);

        return $this->json(['success' => true, 'p_totalCount' => $totalCount, 'p_results' => $data]);
    }
}
