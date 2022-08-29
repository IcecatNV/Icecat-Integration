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

    function  processRecords()
    {
        $configs = Configuration::load();
        $productClass = $configs->getProductClass();
        if (empty($productClass)) {
            p_r('Product class not set!!');
            return ;
        }
        $gtinField = $configs->getGtinField();
        $brandNameField = $configs->getBrandNameField();
        $productNameField = $configs->getProductNameField();

        if (empty($gtinField) && (empty($brandNameField) || empty($productNameField))) {
            p_r('Either GTIN or (brandName and productName) field(s) must be set!!');
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

    public function refreshProduct($iObjId, $languages)
    {
        $this->iceCatUser = $this->getIcecatLoginUser();
        $languages = explode(',',$languages);
        $iObj = DataObject::getById($iObjId);

        foreach ($languages as $lang) {
            $this->language = $lang;
            $this->importIceCatProduct($iObj, 'Gtin', 'Brand', 'Product_Code');
        }
    }
    public function importIceCatProduct($product, $gtinField, $brandNameField, $productCodeField)
    {
        $res = $this->getIceCatData($product, $gtinField, $brandNameField, $productCodeField);

        if ($res['failure']) {
            // @todo: logging
            return false;
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
        $url = $this->getIceCatUrlToGetRecord($dataToFetchIceProduct, $this->iceCatUser, $this->language);
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
                'gtin' => $currentProductIceCatId,
                'original_gtin' => $gtin,
                'language' => $this->language,
                'data_encoded' => base64_encode($response),
                'product_name' => $productName
            ];

//            p_r($data);

            $result =  [ 'failure' => false, 'iceCatData' => $data];


        }
        return $result;
    }
}
