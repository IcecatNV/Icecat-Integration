<?php

namespace IceCatBundle\Services;

use Pimcore\Tool;
use Symfony\Component\HttpFoundation\Request;

class SearchService extends AbstractService
{
    /**
     * @return bool
     */
    public function isSearchEnable()
    {
        $sql = "SELECT COUNT(*) as c FROM objects WHERE o_classId = 'icecat_category'";
        $result = \Pimcore\Db::get()->fetchAssoc($sql);

        return (bool) $result['c'];
    }

    /**
     * @return array
     */
    public function getSearchLanguages()
    {
        $data = [];
        $activatedLanguage = Tool::getValidLanguages();
        foreach ($activatedLanguage as $lang) {
            $sql = "SELECT COUNT(*) as c FROM object_localized_icecat_category_{$lang} WHERE trim(name) != ''";
            $result = \Pimcore\Db::get()->fetchAssoc($sql);
            if ((int)$result['c'] !== 0) {
                $data[] = $lang;
            }
        }

        return $data;
    }

    /**
     * @param int $id
     * @param string $type
     *
     * @return array
     */
    public function getCSKeyForFeature($id, $type)
    {
        $sql = "SELECT * FROM classificationstore_keys WHERE name = '{$id}{$type}'";
        $result = \Pimcore\Db::get()->fetchAssoc($sql);

        return $result;
    }

    /**
     * @param array $keyData
     * @param string $language
     *
     * @return array
     */
    public function getValuesForCSKey($request, $keyData, $language)
    {
        $category = trim($request->get('categoryID'));
        $brand = trim($request->get('brand'));

        $sql = "SELECT DISTINCT c.value FROM object_localized_Icecat_{$language} o
                INNER JOIN object_classificationstore_data_Icecat c
                ON c.o_id = o.o_id ";

        $sql .= 'WHERE 1=1 ';

        if ($category) {
            $sql .= " AND o.RelatedCategories LIKE '%,{$category},%' ";
        }

        if ($brand) {
            $sql .= " AND o.Brand = '{$brand}' ";
        }

        $sql .= " AND c.keyId = {$keyData['id']} AND c.language = '{$language}'";

        $values = \Pimcore\Db::get()->fetchCol($sql);

        $units = [];
        if ($keyData['type'] == 'quantityValue') {
            $sql = "SELECT DISTINCT c.value2 FROM object_localized_Icecat_{$language} o
                    INNER JOIN object_classificationstore_data_Icecat c
                    ON c.o_id = o.o_id ";

            $sql .= 'WHERE 1=1 ';

            if ($category) {
                $sql .= " AND o.RelatedCategories LIKE '%,{$category},%' ";
            }

            if ($brand) {
                $sql .= " AND o.Brand = '{$brand}' ";
            }

            $sql .= " AND c.keyId = {$keyData['id']} AND c.language = '{$language}'";

            $unitsResult = \Pimcore\Db::get()->fetchCol($sql);
            foreach ($unitsResult as $u) {
                $sql = "SELECT * FROM quantityvalue_units WHERE id = '{$u}'";
                $unit = \Pimcore\Db::get()->fetchAssoc($sql);
                $units[] = $unit;
            }
        }

        $data = [];
        foreach ($values as $v) {
            if ($keyData['type'] == 'booleanSelect') {
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

    /**
     * @param Request $request
     *
     * @return array
     */
    public function getSearchResultData($request)
    {
        $language = $request->get('language', 'en');
        $start = $request->get('start', 0);
        $limit = $request->get('limit', 50);

        $parameters = $request->request->all();
        $featuresValues = [];
        foreach ($parameters as $param => $values) {
            if (strpos($param, 'feature_unit_') === false && strpos($param, 'feature_') !== false) {
                $tmp = explode('_', $param);
                $featuresValues[$tmp[1]] = $values;
            }
        }

        $sql = "SELECT * FROM object_localized_Icecat_{$language} o ";
        $sql .= $this->getFilterCondition($request);
        $sql .= "LIMIT {$start}, {$limit}";

        $result = \Pimcore\Db::get()->fetchAll($sql);

        $data = [];
        foreach ($result as $r) {
            $data[] = [
                'id' => $r['o_id'],
                'icecat_productid' => $r['Icecat_Product_Id'],
                'producttitle' => $r['productTitle'],
                'productcode' => $r['Product_Code'],
                'brand' => $r['Brand'],
                'gtin' => $r['Gtin'],
                'productfamily' => $r['productFamily'],
            ];
        }

        return $data;
    }

    /**
     * @param Request $request
     *
     * @return int
     */
    public function getSearchResultCount($request)
    {
        $language = $request->get('language', 'en');
        $sql = "SELECT COUNT(*) as c FROM object_localized_Icecat_{$language} o ";
        $sql .= $this->getFilterCondition($request);
        $result = \Pimcore\Db::get()->fetchAssoc($sql);

        return $result['c'] ?? 0;
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    public function getFilterCondition($request)
    {
        $language = $request->get('language', 'en');
        $category = trim($request->get('category'));
        $brand = trim($request->get('brand'));

        $parameters = $request->request->all();
        $featuresValues = [];
        foreach ($parameters as $param => $values) {
            if (strpos($param, 'feature_unit_') === false && strpos($param, 'feature_') !== false) {
                $tmp = explode('_', $param);
                $featuresValues[$tmp[1]] = $values;
            }
        }

        $sql = '';

        if (count($featuresValues)) {
            foreach ($featuresValues as $featureId => $featureV) {
                if (count($featureV)) {
                    $alias = "c{$featureId}";
                    $sql .= "\nINNER JOIN object_classificationstore_data_Icecat {$alias}
                             ON {$alias}.o_id = o.o_id AND {$alias}.keyId = {$featureId} AND ";
                    $sql .= '(';
                    $z = 0;
                    foreach ($featureV as $v) {
                        $sql .= $z != 0 ? 'OR ' : '';
                        $sql .= "{$alias}.value = '{$v}' ";
                        $z++;
                    }
                    $sql .= ') ';
                    if (isset($parameters["feature_unit_{$featureId}"]) && $parameters["feature_unit_{$featureId}"] != '') {
                        $unit = addslashes($parameters["feature_unit_{$featureId}"]);
                        $sql .= "AND {$alias}.value2 = '{$unit}' ";
                    }
                    $sql .= "AND {$alias}.language = '{$language}' ";
                }
            }
        }
        $sql .= 'WHERE 1=1 ';

        if ($category) {
            $sql .= " AND o.RelatedCategories LIKE '%,{$category},%' ";
        }

        if ($brand) {
            $sql .= " AND o.Brand = '{$brand}' ";
        }

        return $sql;
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    public function getBrands($request)
    {
        $category = trim($request->get('category'));
        $language = $request->get('language', 'en');
        $sql = "SELECT DISTINCT(o.Brand) as b FROM object_localized_Icecat_{$language} o";
        $sql .= ' WHERE 1=1 ';
        if ($category) {
            $sql .= " AND o.RelatedCategories LIKE '%,{$category},%' ";
        }

        $result = \Pimcore\Db::get()->fetchCol($sql);

        $data = [];
        foreach ($result as $r) {
            if ($r != '') {
                $data[] = [
                    'key' => $r,
                    'value' => $r
                ];
            }
        }

        return $data;
    }
}
