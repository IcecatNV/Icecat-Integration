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
    }
}