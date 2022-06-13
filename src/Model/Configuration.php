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
                    'categorization' => false
                ]
            ];
        }

        if ($this->getLanguages() !== null) {
            $data['icecat']['languages'] = $this->getLanguages();
        }
        if ($this->getCategorization() !== null) {
            $data['icecat']['categorization'] = $this->getCategorization();
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

                return $config;
            } else {
                return null;
            }
        } catch (\Pimcore\Model\Exception\NotFoundException $e) {
            return null;
        }
    }
}
