<?php

namespace IceCatBundle\Model;

use Pimcore\File;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Configuration
 *
 * @package IceCatBundle
 */
class Configuration
{
    public const CONFIG_PATH = PIMCORE_CONFIGURATION_DIRECTORY . '/icecat';

    /**
     * @var array
     */
    protected $languages;

    /**
     * @var bool
     */
    protected $categorization;

    /**
     * @var bool
     */
    protected $importRelatedProducts;

    /**
     * @var
     */
    protected $productClass;

    /**
     * @var
     */
    protected $gtinField;

    /**
     * @var
     */
    protected $gtinFieldType;

    /**
     * @var
     */
    protected $mappingGtinClassField;

    /**
     * @var
     */
    protected $mappingGtinLanguageField;

    /**
     * @var
     */
    protected $brandNameField;

    /**
     * @var
     */
    protected $brandNameFieldType;

    /**
     * @var
     */
    protected $mappingBrandClassField;

    /**
     * @var
     */
    protected $mappingBrandLanguageField;

    /**
     * @var
     */
    protected $productNameField;

    /**
     * @var
     */
    protected $productNameFieldType;

    /**
     * @var
     */
    protected $mappingProductCodeClassField;

    /**
     * @var
     */
    protected $mappingProductCodeLanguageField;

    /**
     * @var
     */
    protected $cronExpression;

    /**
     * @var
     */
    protected $assetFilePath;

    /**
     * @var
     */
    protected $onlyNewObjectProcessed;

    /**
     * @return bool
     */
    public function getImportRelatedProducts()
    {
        return $this->importRelatedProducts;
    }

    /**
     * @param bool $importRelatedProducts
     */
    public function setImportRelatedProducts($importRelatedProducts)
    {
        $this->importRelatedProducts = $importRelatedProducts;
        return $this;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->languages = $this->categorization = null;
    }

    /**
     * @param array $languages
     *
     * @return self
     */
    public function setLanguages($languages)
    {
        $this->languages = $languages;

        return $this;
    }

    /**
     * @param bool $languages
     *
     * @return self
     */
    public function setCategorization($categorization)
    {
        $this->categorization = $categorization;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getLanguages()
    {
        return $this->languages;
    }

    /**
     * @return bool|null
     */
    public function getCategorization()
    {
        return $this->categorization;
    }

    /**
     * @return mixed
     */
    public function getProductClass()
    {
        return $this->productClass;
    }

    /**
     * @param mixed $productClass
     */
    public function setProductClass($productClass)
    {
        $this->productClass = $productClass;
        return $this;
    }

    /**
     * @param mixed $gtinField
     */
    public function setGtinField($gtinField)
    {
        $this->gtinField = $gtinField;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getGtinField()
    {
        return $this->gtinField;
    }

    /**
     * @return mixed
     */
    public function setGtinFieldType($gtinFieldType)
    {
        $this->gtinFieldType = $gtinFieldType;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getGtinFieldType()
    {
        return $this->gtinFieldType;
    }

    /**
     * @param mixed $mappingGtinClassField
     */
    public function setMappingGtinClassField($mappingGtinClassField)
    {
        $this->mappingGtinClassField = $mappingGtinClassField;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMappingGtinClassField()
    {
        return $this->mappingGtinClassField;
    }

    /**
     * @param mixed $mappingGtinLanguageField
     */
    public function setMappingGtinLanguageField($mappingGtinLanguageField)
    {
        $this->mappingGtinLanguageField = $mappingGtinLanguageField;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMappingGtinLanguageField()
    {
        return $this->mappingGtinLanguageField;
    }

    /**
     * @return mixed
     */
    public function getBrandNameField()
    {
        return $this->brandNameField;
    }

    /**
     * @param mixed $brandNameField
     */
    public function setBrandNameField($brandNameField)
    {
        $this->brandNameField = $brandNameField;
        return $this;
    }

    /**
     * @param mixed $brandNameFieldType
     */
    public function setBrandNameFieldType($brandNameFieldType)
    {
        $this->brandNameFieldType = $brandNameFieldType;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBrandNameFieldType()
    {
        return $this->brandNameFieldType;
    }


    /**
     * @param mixed $mappingBrandClassField
     */
    public function setMappingBrandClassField($mappingBrandClassField)
    {
        $this->mappingBrandClassField = $mappingBrandClassField;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMappingBrandClassField()
    {
        return $this->mappingBrandClassField;
    }

    /**
     * @param mixed $mappingBrandLanguageField
     */
    public function setMappingBrandLanguageField($mappingBrandLanguageField)
    {
        $this->mappingBrandLanguageField = $mappingBrandLanguageField;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMappingBrandLanguageField()
    {
        return $this->mappingBrandLanguageField;
    }

    /**
     * @return mixed
     */
    public function getProductNameField()
    {
        return $this->productNameField;
    }

    /**
     * @param mixed $productNameField
     */
    public function setProductNameField($productNameField)
    {
        $this->productNameField = $productNameField;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getProductNameFieldType()
    {
        return $this->productNameFieldType;
    }

    /**
     * @param mixed $productNameFieldType
     */
    public function setProductNameFieldType($productNameFieldType)
    {
        $this->productNameFieldType = $productNameFieldType;
        return $this;
    }

    /**
     * @param mixed $mappingProductCodeClassField
     */
    public function setMappingProductCodeClassField($mappingProductCodeClassField)
    {
        $this->mappingProductCodeClassField = $mappingProductCodeClassField;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMappingProductCodeClassField()
    {
        return $this->mappingProductCodeClassField;
    }

    /**
     * @param mixed $mappingProductCodeLanguageField
     */
    public function setMappingProductCodeLanguageField($mappingProductCodeLanguageField)
    {
        $this->mappingProductCodeLanguageField = $mappingProductCodeLanguageField;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMappingProductCodeLanguageField()
    {
        return $this->mappingProductCodeLanguageField;
    }

    /**
     * @return mixed
     */
    public function getCronExpression()
    {
        return $this->cronExpression;
    }

    /**
     * @param mixed $assetFilePath
     */
    public function setAssetFilePath($assetFilePath)
    {
        $this->assetFilePath = $assetFilePath;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAssetFilePath()
    {
        return $this->assetFilePath;
    }

    /**
     * @param mixed $cronExpression
     */
    public function setCronExpression($cronExpression)
    {
        $this->cronExpression = $cronExpression;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getOnlyNewObjectProcessed()
    {
        return $this->onlyNewObjectProcessed;
    }

    /**
     * @param mixed $onlyNewObjectProcessed
     */
    public function setOnlyNewObjectProcessed($onlyNewObjectProcessed)
    {
        $this->onlyNewObjectProcessed = $onlyNewObjectProcessed;
        return $this;
    }

    /**
     * @throws \Exception
     */
    public function save(): void
    {
        if (is_readable(self::CONFIG_PATH.'/config.yaml')) {
            $data = Yaml::parseFile(self::CONFIG_PATH.'/config.yaml');
        } else {
            $data = [
                'icecat' => [
                    'languages' => ['en'],
                    'categorization' => false,
                    'importRelatedProducts' => false,
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
                    'onlyNewObjectProcessed' => true
                ]
            ];
        }

        if ($this->getLanguages() !== null) {
            $data['icecat']['languages'] = $this->getLanguages();
        }
        if ($this->getCategorization() !== null) {
            $data['icecat']['categorization'] = $this->getCategorization();
        }

        if ($this->getImportRelatedProducts() !== null) {
            $data['icecat']['importRelatedProducts'] = $this->getImportRelatedProducts();
        }

        if ($this->getProductClass() !== null) {
            $data['icecat']['productClass'] = $this->getProductClass();
        }

        if ($this->getGtinField() !== null) {
            if ($this->getMappingGtinClassField() !== null) {
                $data['icecat']['gtinField']['name'] = $this->getGtinField();
                $data['icecat']['gtinField']['type'] = $this->getGtinFieldType();
                $data['icecat']['gtinField']['referenceFieldName'] = $this->getMappingGtinClassField();
            } else {
                $data['icecat']['gtinField']['name'] = $this->getGtinField();
                $data['icecat']['gtinField']['type'] = "default";
            }
            $data['icecat']['gtinField']['language'] = $this->getMappingGtinLanguageField();
        }

        if ($this->getBrandNameField() !== null) {
            if ($this->getMappingBrandClassField() !== null) {
                $data['icecat']['brandNameField']['name'] = $this->getBrandNameField();
                $data['icecat']['brandNameField']['type'] = $this->getBrandNameFieldType();
                $data['icecat']['brandNameField']['referenceFieldName'] = $this->getMappingBrandClassField();
            } else {
                $data['icecat']['brandNameField']['name'] = $this->getBrandNameField();
                $data['icecat']['brandNameField']['type'] = "default";
            }
            $data['icecat']['brandNameField']['language'] = $this->getMappingBrandLanguageField();
        }

        if ($this->getProductNameField() !== null) {
            if ($this->getMappingProductCodeClassField() !== null) {
                $data['icecat']['productNameField']['name'] = $this->getProductNameField();
                $data['icecat']['productNameField']['type'] = $this->getProductNameFieldType();
                $data['icecat']['productNameField']['referenceFieldName'] = $this->getMappingProductCodeClassField();
            } else {
                $data['icecat']['productNameField']['name'] = $this->getProductNameField();
                $data['icecat']['productNameField']['type'] = "default";
            }
            $data['icecat']['productNameField']['language'] = $this->getMappingProductCodeLanguageField();
        }

        if ($this->getCronExpression() !== null) {
            $data['icecat']['cronExpression'] = $this->getCronExpression();
        }

        if ($this->getAssetFilePath() !== null) {
            $data['icecat']['assetFilePath'] = $this->getAssetFilePath();
        }

        if ($this->getOnlyNewObjectProcessed() !== null) {
            $data['icecat']['onlyNewObjectProcessed'] = $this->getOnlyNewObjectProcessed();
        }

        File::put(self::CONFIG_PATH.'/config.yaml', Yaml::dump($data, 4));
    }

    /**
     * @param $name
     *
     * @return Configuration|null
     */
    public static function load(): ?self
    {
        try {
            if (is_readable(self::CONFIG_PATH.'/config.yaml')) {
                $data = Yaml::parseFile(self::CONFIG_PATH.'/config.yaml');
                $config = new self();
                $config->setLanguages($data['icecat']['languages'] ?? []);
                $config->setCategorization($data['icecat']['categorization'] ?? false);
                $config->setImportRelatedProducts($data['icecat']['importRelatedProducts'] ?? false);
                $config->setProductClass($data['icecat']['productClass'] ?? null);

                $config->setGtinField($data['icecat']['gtinField']['name'] ?? null);
                $config->setGtinFieldType($data['icecat']['gtinField']['type'] ?? null);
                $config->setMappingGtinClassField($data['icecat']['gtinField']['referenceFieldName'] ?? null);
                $config->setMappingGtinLanguageField($data['icecat']['gtinField']['language'] ?? null);

                $config->setBrandNameField($data['icecat']['brandNameField']['name'] ?? null);
                $config->setBrandNameFieldType($data['icecat']['brandNameField']['type'] ?? null);
                $config->setMappingBrandClassField($data['icecat']['brandNameField']['referenceFieldName'] ?? null);
                $config->setMappingBrandLanguageField($data['icecat']['brandNameField']['language'] ?? null);

                $config->setProductNameField($data['icecat']['productNameField']['name'] ?? null);
                $config->setProductNameFieldType($data['icecat']['productNameField']['type'] ?? null);
                $config->setMappingProductCodeClassField($data['icecat']['productNameField']['referenceFieldName'] ?? null);
                $config->setMappingProductCodeLanguageField($data['icecat']['productNameField']['language'] ?? null);

                $config->setCronExpression($data['icecat']['cronExpression'] ?? null);
                $config->setAssetFilePath($data['icecat']['assetFilePath'] ?? null);
                $config->setOnlyNewObjectProcessed($data['icecat']['onlyNewObjectProcessed'] ?? true);

                return $config;
            } else {
                return null;
            }
        } catch (\Pimcore\Model\Exception\NotFoundException $e) {
            return null;
        }
    }
}
