<?php

declare(strict_types=1);

namespace IceCatBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220907163548 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Alter icecat_user_login table name';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE `icecat_user_login`
            ADD IF NOT EXISTS `session_id` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            ADD IF NOT EXISTS `access_token` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            ADD IF NOT EXISTS `content_token` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            ADD IF NOT EXISTS `app_key` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            ADD IF NOT EXISTS `icecat_password` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL '
        );

        $this->addSql('CREATE TABLE IF NOT EXISTS `icecat_recurring_import` (
            `id` int NOT NULL AUTO_INCREMENT,
            `start_datetime` int NOT NULL,
            `end_datetime` int NOT NULL,
            `status` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            `total_records` int NOT NULL,
            `processed_records` int NOT NULL,
            `success_records` int NOT NULL,
            `error_records` int NOT NULL,
            `not_found_records` int NOT NULL,
            `forbidden_records` int NOT NULL,
            `execution_type` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            PRIMARY KEY (`id`)
          ) ENGINE=InnoDB AUTO_INCREMENT=277 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;');

        $this->addSql(
            'ALTER TABLE `icecat_recurring_import`
            MODIFY `status` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            MODIFY `execution_type` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL'
        );
    }



    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `icecat_user_login`
        DROP COLUMN  IF  EXISTS `session_id`  ,
        DROP COLUMN  IF  EXISTS `access_token` ,
        DROP COLUMN  IF  EXISTS `content_token`,
        DROP COLUMN  IF  EXISTS `app_key`,
        DROP COLUMN  IF  EXISTS `icecat_password`
        ');

        // this is done deliberatley because when installing v3 sometimes this table doesn't
        // get created due to collate issue.
        // so add it if one is downgrading from v4 to v3
        $this->addSql('CREATE TABLE IF NOT EXISTS `icecat_recurring_import` (
            `id` int NOT NULL AUTO_INCREMENT,
            `start_datetime` int NOT NULL,
            `end_datetime` int NOT NULL,
            `status` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            `total_records` int NOT NULL,
            `processed_records` int NOT NULL,
            `success_records` int NOT NULL,
            `error_records` int NOT NULL,
            `not_found_records` int NOT NULL,
            `forbidden_records` int NOT NULL,
            `execution_type` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=277 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;');

        $this->addSql(
            'ALTER TABLE `icecat_recurring_import`
            MODIFY `status` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
            MODIFY `execution_type` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL'
        );
    }
}
