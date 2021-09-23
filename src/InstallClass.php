<?php

namespace IceCatBundle;

use Pimcore\Extension\Bundle\Installer\AbstractInstaller;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject;

class InstallClass extends AbstractInstaller
{

    const STORE_NAME = 'icecat-store';
    protected $storeId = 1;

    /**
     * @var BufferedOutput
     */
    protected $output;

    public function __construct()
    {
        $this->output = new BufferedOutput(Output::VERBOSITY_NORMAL, true);
    }

    /**
     * {@inheritdoc}
     */
    public function install()
    {

        /* create Icecat class */
        $this->createClassificationStore();
        $this->createclassdefinition();
        $this->createTable();
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall()
    {
        //$this->removeClass();
    }

    /**
     * {@inheritdoc}
     */
    public function isInstalled()
    {
        // $memberClass = ClassDefinition::getByName('Icecat');
        // if ($memberClass) {
        //     return true;
        // }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function canBeInstalled()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function canBeUninstalled()
    {
        return true;
    }

    /**
     * @return OutputInterface
     */
    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    public function removeClass()
    {
        $class = ClassDefinition::getByName('Icecat');
        $class->delete();
    }

    public function createTable()
    {
        $db = \Pimcore\Db::get();
        $db->query(
            "DROP TABLE IF EXISTS `ice_cat_processes`;
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
        "
        );

        $db->query(

            "

            DROP TABLE IF EXISTS `icecat_imported_data`;
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            
            "
        );

        $db->query(

            "DROP TABLE IF EXISTS `icecat_user_login`;
                CREATE TABLE `icecat_user_login` (
                `id` int(5) NOT NULL AUTO_INCREMENT,
                `icecat_user_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
                `pim_user_id` int(5) NOT NULL,
                `login_status` int(2) DEFAULT 1,
                `lastactivity_time` datetime DEFAULT NULL,
                `creation_time` datetime DEFAULT NULL,
                PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
        );
    }

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
    public function createclassdefinition()
    {
        $classname = 'Icecat';
        $filepath = __DIR__ . '/Install/class_Icecat_export.json';
        $class = \Pimcore\Model\DataObject\ClassDefinition::getByName($classname);
        if (!$class) {
            $class = new \Pimcore\Model\DataObject\ClassDefinition();
            $class->setName($classname);
            $class->setGroup('Icecat');

            $json = file_get_contents($filepath);

            $success = \Pimcore\Model\DataObject\ClassDefinition\Service::importClassDefinitionFromJson($class, $json);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function needsReloadAfterInstall()
    {
        return true;
    }
}