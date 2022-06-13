<?php

declare(strict_types=1);

namespace IceCatBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220423095622 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $classname = 'Icecat';
        $filepath = __DIR__ . '/../Install/class_Icecat_export_v1.json';
        $class = \Pimcore\Model\DataObject\ClassDefinition::getByName($classname);
        if ($class) {
            $json = file_get_contents($filepath);
            \Pimcore\Model\DataObject\ClassDefinition\Service::importClassDefinitionFromJson($class, $json);
        }

        $classname = 'IcecatCategory';
        $filepath = __DIR__ . '/../Install/class_IcecatCategory_export_v1.json';
        $class = \Pimcore\Model\DataObject\ClassDefinition::getByName($classname);

        if (!$class) {
            $class = new \Pimcore\Model\DataObject\ClassDefinition();
            $class->setName($classname);
            $class->setGroup('Icecat');
        }
        $json = file_get_contents($filepath);
        \Pimcore\Model\DataObject\ClassDefinition\Service::importClassDefinitionFromJson($class, $json);
    }

    public function down(Schema $schema): void
    {
        $classname = 'Icecat';
        $filepath = __DIR__ . '/../Install/class_Icecat_export.json';
        $class = \Pimcore\Model\DataObject\ClassDefinition::getByName($classname);
        if ($class) {
            $json = file_get_contents($filepath);
            \Pimcore\Model\DataObject\ClassDefinition\Service::importClassDefinitionFromJson($class, $json);
        }

        $classname = 'IcecatCategory';
        $class = \Pimcore\Model\DataObject\ClassDefinition::getByName($classname);
        if ($class) {
            $class->delete();
        }
    }
}
