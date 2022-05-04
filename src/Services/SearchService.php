<?php

namespace IceCatBundle\Services;

class SearchService extends AbstractService
{

    public function getCSKeyForFeature($id)
    {
        $sql = "SELECT * FROM classificationstore_keys WHERE name LIKE '{$id}%'";
        $result = \Pimcore\Db::get()->fetchAssoc($sql);
        return $result;
    }

    public function getValuesForCSKey($keyData, $language)
    {
        $sql = "SELECT DISTINCT value FROM object_classificationstore_data_Icecat WHERE keyId = {$keyData['id']} AND language = '{$language}'";
        $values = \Pimcore\Db::get()->fetchCol($sql);

        $units = [];
        if($keyData['type'] == "quantityValue") {
            $sql = "SELECT DISTINCT value2 FROM object_classificationstore_data_Icecat WHERE keyId = {$keyData['id']} AND language = '{$language}'";
            $unitsResult = \Pimcore\Db::get()->fetchCol($sql);
            foreach($unitsResult as $u) {
                $sql = "SELECT * FROM quantityvalue_units WHERE id = '{$u}'";
                $unit = \Pimcore\Db::get()->fetchAssoc($sql);
                $units[] = $unit;
            }
        }


        $data = [];
        foreach($values as $v) {
            if($keyData['type'] == "booleanSelect") {
                $data[] = [
                    'key' => $v,
                    'value' => $v == 1 ? 'Yes' : 'No'
                ];
            } else {
                $data[] = [
                    'key' => $v,
                    'value' => $v
                ];
            }

        }

        return [
            'values' => $data,
            'units' => $units
        ];
    }

    public function getSearchResultData($request)
    {
        $language = $request->get('language', 'en');
        $category = $request->get('category');
        $start = $request->get('start', 0);
        $limit = $request->get('limit', 50);

        $parameters = $request->request->all();
        $featuresValues = [];
        foreach($parameters as $param => $values) {
            if(strpos($param, 'feature_') !== false) {
                $tmp = explode('_', $param);
                $featuresValues[$tmp[1]] = $values;
            }
        }

        $sql = "SELECT * FROM object_localized_Icecat_{$language} ";
        $sql .= "WHERE 1=1 ";
        if($category) {
            $sql .= " AND RelatedCategories LIKE '%,{$category},%' ";
        }
        $sql .= "LIMIT {$start}, {$limit}";
        $result = \Pimcore\Db::get()->fetchAll($sql);

        $data = [];
        foreach($result as $r) {
            $data[] = [
                'id'=> $r['o_id'],
                'icecat_productid'=> $r['Icecat_Product_Id'],
                'producttitle'=> $r['productTitle'],
                'productcode'=> $r['Product_Code'],
                'brand'=> $r['Brand'],
                'gtin'=> $r['Gtin'],
                'productfamily'=> $r['productFamily'],
            ];
        }
        return $data;
    }

    public function getSearchResultCount($request)
    {
        $language = $request->get('language', 'en');
        $start = $request->get('start', 0);
        $limit = $request->get('limit', 50);
        $sql = "SELECT COUNT(*) FROM object_localized_Icecat_{$language}";
        $result = \Pimcore\Db::get()->fetchCol($sql);
        return $result[0];
    }
}
