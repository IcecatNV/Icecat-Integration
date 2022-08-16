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
    protected $brandNameField;

    /**
     * @var
     */
    protected $productNameField;

    /**
     * @var 
     */
    protected $cronExpression;

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
     * @return mixed
     */
    public function getGtinField()
    {
        return $this->gtinField;
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
    public function getCronExpression()
    {
        return $this->cronExpression;
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
                    'productClass' => -1,
                    'gtinField' => -1,
                    'brandNameField' => -1,
                    'productNameField' => -1,
                    'cronExpression' => '',
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
            $data['icecat']['gtinField'] = $this->getGtinField();
        }

        if ($this->getBrandNameField() !== null) {
            $data['icecat']['brandNameField'] = $this->getBrandNameField();
        }

        if ($this->getProductNameField() !== null) {
            $data['icecat']['productNameField'] = $this->getProductNameField();
        }

        if ($this->getCronExpression() !== null) {
            $data['icecat']['cronExpression'] = $this->getCronExpression();
        }

        File::put(self::CONFIG_PATH.'/config.yaml', Yaml::dump($data));
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
                $config->setProductClass($data['icecat']['productClass'] ?? -1);
                $config->setGtinField($data['icecat']['gtinField'] ?? -1);
                $config->setBrandNameField($data['icecat']['brandNameField'] ?? -1);
                $config->setProductNameField($data['icecat']['productNameField'] ?? -1);
                $config->setCronExpression($data['icecat']['cronExpression'] ?? '');

                return $config;
            } else {
                return null;
            }
        } catch (\Pimcore\Model\Exception\NotFoundException $e) {
            return null;
        }
    }




}
