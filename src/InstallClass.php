<?php

namespace IceCatBundle;

use IceCatBundle\Migrations\Version20220423095622;
use IceCatBundle\Model\Configuration;
use Pimcore\Extension\Bundle\Installer\SettingsStoreAwareInstaller;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition;

class InstallClass extends SettingsStoreAwareInstaller
{
    /**
     * @var string
     */
    const PRODUCT_FOLDER_PATH = '/ICECAT';

    /**
     * @var string
     */
    const CATEGORY_FOLDER_PATH = '/ICECAT/CATEGORIES';

    /**
     * @var string
     */
    const STORE_NAME = 'icecat-store';

    /**
     * @var int
     */
    protected $storeId = 1;

    /**
     * {@inheritdoc}
     */
    public function install()
    {
        $this->createClassificationStore();
        $this->createClassDefinition();
        $this->createTables();

        parent::install();
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall()
    {
        $this->removeClass();
        $this->removeTables();
        $this->removeClassificationStore();

        if (is_readable(Configuration::CONFIG_PATH.'/config.yaml')) {
            @unlink(Configuration::CONFIG_PATH.'/config.yaml');
        }

        parent::uninstall();
    }

    /**
     * {@inheritdoc}
     */
    public function isInstalled()
    {
        $memberClass = ClassDefinition::getByName('Icecat');
        if ($memberClass) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function canBeInstalled()
    {
        $memberClass = ClassDefinition::getByName('Icecat');
        if (!$memberClass) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function canBeUninstalled()
    {
        $memberClass = ClassDefinition::getByName('Icecat');
        if ($memberClass) {
            return true;
        }

        return false;
    }

    /**
     * Remove Icecat class
     *
     * @return void
     */
    public function removeClass()
    {
        $class = \Pimcore\Model\DataObject\ClassDefinition::getByName('IcecatCategory');
        if ($class) {
            try {
                $class->delete();
            } catch (\Throwable $e) {
                p_r($e);
                die;
                // do fancy things here ..
            }
        }

        $class = ClassDefinition::getByName('Icecat');
        if ($class) {
            try {
                $class->delete();
            } catch (\Throwable $e) {
                // do fancy things here ..
            }
        }

        $folder = DataObject\Folder::getByPath('/ICECAT');
        if ($folder) {
            try {
                $folder->delete();
            } catch (\Throwable $e) {
                // do fancy things here ..
            }
        }
    }

    /**
     * Create icecat bundle related tables
     *
     * @return void
     */
    public function createTables()
    {
        $db = \Pimcore\Db::get();
        $db->query(
            'DROP TABLE IF EXISTS `ice_cat_processes`;
            CREATE TABLE `ice_cat_processes` (
            `total_languages` int(5) NOT NULL DEFAULT 1,
            `file_row_count` int(11) NOT NULL,
            `fetched_blank_records` int(11) DEFAULT NULL,
            `languages` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
            `pimcore_user_id` int(11) NOT NULL,
            `icecat_user_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `filename` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
            `file_extension` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
            `jobid` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
            `total_records` int(11) DEFAULT NULL,
            `total_fetch_records` int(11) DEFAULT NULL,
            `fetched_records` int(11) DEFAULT NULL,
            `processed_records` int(11) DEFAULT NULL,
            `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `fetching_status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `processing_status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `completed` tinyint(4) NOT NULL DEFAULT 0,
            `fetching_error` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `processing_error` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `starting_dateTime` datetime DEFAULT NULL,
            `last_run_dateTime` datetime DEFAULT NULL,
            `end_dateTime` datetime DEFAULT NULL,
            `created_at` datetime NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        '
        );

        $db->query(
            "DROP TABLE IF EXISTS `icecat_imported_data`;
            CREATE TABLE `icecat_imported_data` (
              `original_gtin` varchar(111) COLLATE utf8mb4_unicode_ci NOT NULL,
              `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `to_be_created` tinyint(4) NOT NULL DEFAULT 1,
              `language` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
              `is_product_proccessed` tinyint(4) NOT NULL DEFAULT 0,
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `job_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
              `gtin` varchar(111) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `data_encoded` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
              `data` blob DEFAULT NULL,
              `pim_user_id` int(11) NOT NULL,
              `icecat_username` varchar(110) COLLATE utf8mb4_unicode_ci NOT NULL,
              `product_name` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `is_product_found` int(2) DEFAULT NULL,
              `duplicate` int(11) DEFAULT 0,
              `error` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
              `base_file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `search_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `created_at` datetime NOT NULL DEFAULT current_timestamp(),
              `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
              PRIMARY KEY (`id`),
              UNIQUE KEY `gtin_icecat_username_search_key_language_job_id` (`gtin`,`icecat_username`,`search_key`,`language`,`job_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
        );

        $db->query(
            'DROP TABLE IF EXISTS `icecat_user_login`;
                CREATE TABLE `icecat_user_login` (
                `id` int(5) NOT NULL AUTO_INCREMENT,
                `icecat_user_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
                `pim_user_id` int(5) NOT NULL,
                `login_status` int(2) DEFAULT 1,
                `lastactivity_time` datetime DEFAULT NULL,
                `creation_time` datetime DEFAULT NULL,
                PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
        );
    }

    /**
     * Remove icecat bundle related tables
     *
     * @return void
     */
    public function removeTables()
    {
        $db = \Pimcore\Db::get();
        $db->query(
            'DROP TABLE IF EXISTS ice_cat_processes'
        );
        $db->query(
            'DROP TABLE IF EXISTS icecat_imported_data'
        );
        $db->query(
            'DROP TABLE IF EXISTS icecat_user_login'
        );
    }

    /**
     * Create classification store for Icecat product attributes
     *
     * @return void
     */
    public function createClassificationStore()
    {
        $name = self::STORE_NAME;
        $config = \Pimcore\Model\DataObject\Classificationstore\StoreConfig::getByName($name);

        if (!$config) {
            $config = new  \Pimcore\Model\DataObject\Classificationstore\StoreConfig();
            $config->setName($name);
            $config->save();

            $this->storeId = $config->getId();
        } else {
            $this->storeId = $config->getId();
        }
    }

    /**
     * Remove classification store for icecat
     *
     * @return void
     */
    public function removeClassificationStore()
    {
        $db = \Pimcore\Db::get();
        $db->query(
            'DROP TABLE IF EXISTS `object_classificationstore_data_Icecat`'
        );
        $db->query(
            'DROP TABLE IF EXISTS `object_classificationstore_groups_Icecat`'
        );
        $name = self::STORE_NAME;
        $config = \Pimcore\Model\DataObject\Classificationstore\StoreConfig::getByName($name);

        if ($config) {
            $collectionIds = $db->fetchAll("SELECT * FROM classificationstore_collections where storeId = {$config->getId()}");
            $groupIds = $db->fetchAll("SELECT * FROM classificationstore_groups where storeId = {$config->getId()}");

            foreach ($collectionIds as $collectionId) {
                $db->query(
                    "DELETE FROM classificationstore_collectionrelations WHERE colId = {$collectionId['id']}"
                );
            }

            foreach ($groupIds as $groupId) {
                $db->query(
                    "DELETE FROM classificationstore_relations WHERE groupId = {$groupId['id']}"
                );
            }

            $db->query(
                "DELETE FROM classificationstore_collections WHERE storeId = {$config->getId()}"
            );

            $db->query(
                "DELETE FROM classificationstore_groups WHERE storeId = {$config->getId()}"
            );

            $db->query(
                "DELETE FROM classificationstore_keys WHERE storeId = {$config->getId()}"
            );

            $db->query(
                "DELETE FROM classificationstore_stores WHERE id = {$config->getId()}"
            );
        }
    }

    /**
     * Create Icecat class
     *
     * @return void
     */
    public function createClassDefinition()
    {
        $classname = 'Icecat';
        $filepath = __DIR__ . '/Install/class_Icecat_export_v3.json';
        $class = \Pimcore\Model\DataObject\ClassDefinition::getByName($classname);
        if (!$class) {
            $class = new \Pimcore\Model\DataObject\ClassDefinition();
            $class->setName($classname);
            $class->setGroup('Icecat');
            $json = file_get_contents($filepath);
            \Pimcore\Model\DataObject\ClassDefinition\Service::importClassDefinitionFromJson($class, $json);
        }

        // set store id
        $classConfig = \json_decode($json, true);
        $classConfig['layoutDefinitions']['childs'][0]['childs'][3]['childs'][0]['storeId'] = $this->storeId;
        \Pimcore\Model\DataObject\ClassDefinition\Service::importClassDefinitionFromJson($class, \json_encode($classConfig));

        $classname = 'IcecatCategory';
        $filepath = __DIR__ . '/Install/class_IcecatCategory_export_v1.json';
        $class = \Pimcore\Model\DataObject\ClassDefinition::getByName($classname);
        if (!$class) {
            $class = new \Pimcore\Model\DataObject\ClassDefinition();
            $class->setName($classname);
            $class->setGroup('Icecat');
            $json = file_get_contents($filepath);
            \Pimcore\Model\DataObject\ClassDefinition\Service::importClassDefinitionFromJson($class, $json);
        }

        $classname = 'IcecatFieldsLog';
        $filepath = __DIR__ . '/Install/class_IcecatFieldsLog_export_v3.json';
        $class = \Pimcore\Model\DataObject\ClassDefinition::getByName($classname);
        if (!$class) {
            $class = new \Pimcore\Model\DataObject\ClassDefinition();
            $class->setName($classname);
            $class->setGroup('Icecat');
            $json = file_get_contents($filepath);
            \Pimcore\Model\DataObject\ClassDefinition\Service::importClassDefinitionFromJson($class, $json);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function needsReloadAfterInstall()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastMigrationVersionClassName(): ?string
    {
        return Version20220423095622::class;
    }
}
