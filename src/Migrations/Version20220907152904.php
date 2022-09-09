<?php

declare(strict_types=1);

namespace IceCatBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220907152904 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create recurring import table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS `icecat_recurring_import` (
            `id` int NOT NULL AUTO_INCREMENT,
            `start_datetime` int NOT NULL,
            `end_datetime` int NOT NULL,
            `status` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
            `total_records` int NOT NULL,
            `processed_records` int NOT NULL,
            `success_records` int NOT NULL,
            `error_records` int NOT NULL,
            `not_found_records` int NOT NULL,
            `forbidden_records` int NOT NULL,
            `execution_type` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
            PRIMARY KEY (`id`)
          ) ENGINE=InnoDB AUTO_INCREMENT=277 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS icecat_recurring_import;');
    }
}
