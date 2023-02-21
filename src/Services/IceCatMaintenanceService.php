<?php


namespace IceCatBundle\Services;

use IceCatBundle\Lib\IceCateHelper;
use IceCatBundle\Model\Configuration;
use Monolog\Logger;
use Pimcore\Db;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Icecat\Listing;
use Pimcore\Tool;
use Pimcore\Tool\Admin;

class IceCatMaintenanceService
{
    use IceCateHelper;
    public $language = 'en';
    public $iceCatUser = 'sid909';
    public $createObjectService;

    public function __construct(CreateObjectService $createObjectService)
    {
        $this->createObjectService = $createObjectService;
    }

    public $configuration;


    public function importIceCatData()
    {
    }

    public function processRecords()
    {
        $configs = Configuration::load();
        $productClass = $configs->getProductClass();
        if (empty($productClass)) {
            return ;
        }
        $gtinField = $configs->getGtinField();
        $brandNameField = $configs->getBrandNameField();
        $productNameField = $configs->getProductNameField();

        if (empty($gtinField) && (empty($brandNameField) || empty($productNameField))) {
            return ;
        }

        $listingClass = "\\Pimcore\\Model\\DataObject\\" . $productClass . '\\Listing';
        /** @var Listing $listing */
        $listing = new $listingClass();
        // @todo: need to add listing condition
        $productsList = $listing->load();

        foreach ($productsList as $product) {
            $this->importIceCatProduct($product, $gtinField, $brandNameField, $productNameField);
        }
    }

    public function refreshProduct($iObjId, $languages): array
    {
        $this->iceCatUser = $this->getIcecatLoginUser();
        if(!$this->iceCatUser) {
            return [];
        }

        $languages = explode(',', $languages);
        $iObj = DataObject::getById($iObjId);

        foreach ($languages as $lang) {
            $this->language = $lang;
            $return = $this->importIceCatProduct($iObj, 'Gtin', 'Brand', 'Product_Code');

            // check for failure
            if(isset($return['failure']) && $return['failure']) {
                return $return;
            }
        }

        // to make return type strict
        return [];
    }
    public function importIceCatProduct($product, $gtinField, $brandNameField, $productCodeField)
    {
        $res = $this->getIceCatData($product, $gtinField, $brandNameField, $productCodeField);

        if (isset($res['failure']) && $res['failure']) {
            // @todo: logging
            return $res;
        }

        $iceCatData = $res['iceCatData'];
        $this->createObjectService->createIceCatObject($iceCatData);
    }

    public function getIceCatData($product, $gtinField, $brandNameField, $productCodeField)
    {
        $dataToFetchIceProduct = [];
        if (!empty($gtinField)) {
            $dataToFetchIceProduct['gtin'] = $product->{'get' . ucfirst($gtinField)}();
        }
        if (!empty($brandNameField)) {
            $dataToFetchIceProduct['productCode'] = $product->{'get' . ucfirst($brandNameField)}();
        }
        if (!empty($productCodeField)) {
            $dataToFetchIceProduct['brandName'] = $product->{'get' . ucfirst($productCodeField)}();
        }

        $result = [];
        $url = $this->getIceCatUrlToGetRecord($dataToFetchIceProduct, $this->iceCatUser['icecat_user_id'], $this->language);
        if ($url == -1) {
            $reason = ImportService::REASON['INVALID_KEY'];
            $result =  [ 'failure' => true, 'msg' => $reason];
        }

        try {
            $response = \Pimcore::getKernel()->getContainer()->get("IceCatBundle\\Services\\ImportService")->fetchIceCatData($url, $this->iceCatUser['icecat_user_id']);
            $responseArray = json_decode($response, true);

        } catch (\Exception $e) {
            // IN CASE OF INTERNET ACCESSIBLITY IS NOT AVAILABEL OR ICE CAT'S SERVER IS DOWN
            $response = '';
            $responseArray['COULD_NOT_RESOLVE_HOST'] = true;
        }

        $productName = '';
        if (array_key_exists('StatusCode', $responseArray)) {
            if ($responseArray['StatusCode'] == 4) {
                $reason = ImportService::REASON['PRODUCT_NOT_FOUND'];
                $result =  [ 'failure' => true, 'msg' => $reason, 'reason' => 'PRODUCT_NOT_FOUND', 'StatusCode' => $responseArray['StatusCode']];
            } elseif ($responseArray['StatusCode'] == 2) {
                $reason = ImportService::REASON['INVALID_LANGUAGE'];
                $result =  [ 'failure' => true, 'msg' => $reason, 'reason' => 'INVALID_LANGUAGE', 'StatusCode' => $responseArray['StatusCode']];
            } elseif ($responseArray['StatusCode'] == 19) {
                $reason = ImportService::REASON['BAD_REQUEST'];
                $result =  [ 'failure' => true, 'msg' => $reason, 'reason' => 'BAD_REQUEST', 'StatusCode' => $responseArray['StatusCode']];
            } elseif ($responseArray['StatusCode'] == 9) {
                $reason = ImportService::REASON['FORBIDDEN'];
                $result =  [ 'failure' => true, 'msg' => $reason, 'reason' => 'FORBIDDEN', 'StatusCode' => $responseArray['StatusCode']];
            } elseif ($responseArray['StatusCode'] == 1) {
                $reason = ImportService::REASON['USER_MISSING'];
                $result =  [ 'failure' => true, 'msg' => $reason, 'reason' => 'USER_MISSING', 'StatusCode' => $responseArray['StatusCode']];
            } else {
                $reason = ImportService::REASON['UNHANDLED'];
                $result =  [ 'failure' => true, 'msg' => $reason, 'reason' => 'UNHANDLED', 'StatusCode' => 23];
            }
        } elseif (array_key_exists('COULD_NOT_RESOLVE_HOST', $responseArray)) {
            $reason = ImportService::REASON['COULD_NOT_RESOLVE_HOST'];
            $result =  [ 'failure' => true, 'msg' => $reason, 'reason' => 'COULD_NOT_RESOLVE_HOST', 'StatusCode' => 22];
        } elseif (array_key_exists('msg', $responseArray) && $responseArray['msg'] == 'OK') {
            //Product Found
            $gtin = $responseArray['data']['GeneralInfo']['GTIN'][0];
            $currentProductIceCatId = $responseArray['data']['GeneralInfo']['IcecatId'];
            $productName = $productName = str_replace("'", "''", $responseArray['data']['GeneralInfo']['ProductName']);

            $data = [
                'gtin' => $currentProductIceCatId,
                'original_gtin' => $gtin,
                'language' => $this->language,
                'data_encoded' => base64_encode($response),
                'product_name' => $productName
            ];

            $result =  [ 'failure' => false, 'iceCatData' => $data];
        }
        return $result;
    }
}
