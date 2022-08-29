<?php

declare(strict_types=1);

namespace IceCatBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Pimcore\Db;
use Pimcore\Model\DataObject\Icecat\Listing;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220717095622 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $db = Db::get();
        $query = "SELECT oo_id, Video, videos from object_query_Icecat where Video is not null";
        $records = $db->fetchAllAssociative($query);
        $rowCounter = 1;
        $batchSize = 1000;
        $totalRecords = count($records);
        $db->beginTransaction();
        $querySql = "UPDATE object_query_Icecat set videos=:videos  WHERE oo_id=:recId";
        $stmtQuery = $db->prepare($querySql);
        $relationSql = 'INSERT INTO object_relations_Icecat (`src_id`, `dest_id`, `type`, `fieldname`, `index`, `ownertype`, `ownername`, `position`) VALUES ';
        $depSql = 'INSERT IGNORE INTO dependencies (sourcetype, sourceid, targettype, targetid) VALUES ';
        $relationDeleteSql = "DELETE FROM object_relations_Icecat  WHERE src_id= :srcId AND dest_id= :destId AND fieldname='videos' AND type='asset'";
        $relationDelStmt = $db->prepare($relationDeleteSql);
        foreach ($records as $index => $row) {
            $recId = $row['oo_id'];
            $vData = unserialize($row['Video']);
            $videoId = $vData['data'];

            $video = ",asset|" . $videoId . ",";
            if (empty($row['videos'])) {
                $video = ",asset|" . $videoId . ",";
            } else {
                if (str_contains($row['videos'], $video) == false) {
                    $video = $row['videos'] . "asset|" . $videoId . ",";
                } else {
                    $video = $row['videos'];
                }
            }
            if ($recId && $videoId) {
                $stmtQuery->bindValue(':videos', $video);
                $stmtQuery->bindValue(':recId', $recId);
                $stmtQuery->executeQuery();

                $relationDelStmt->bindValue(':srcId', $recId);
                $relationDelStmt->bindValue(':destId', $videoId);
                $relationDelStmt->executeQuery();

                $relationSql .= " ({$recId}, {$db->quote($videoId)}, 'asset', 'videos', 1, 'object', '', 0), ";
                $depSql .= " ( 'object', {$recId}, 'asset', {$videoId}), ";
            }
            if ($rowCounter == $batchSize  || ($index == ($totalRecords-1))) {
                try {
                    $rowCounter = 1;
                    $db->exec(rtrim($relationSql, ', '));
                    $db->exec(rtrim($depSql, ', '));
                    $db->commit();

                    $querySql = "UPDATE object_query_Icecat set videos=:videos  WHERE oo_id=:recId";
                    $stmtQuery = $db->prepare($querySql);
                    $relationSql = 'INSERT INTO object_relations_Icecat (`src_id`, `dest_id`, `type`, `fieldname`, `index`, `ownertype`, `ownername`, `position`) VALUES ';
                    $depSql = 'INSERT IGNORE INTO dependencies (sourcetype, sourceid, targettype, targetid) VALUES ';
                    $relationDeleteSql = "DELETE FROM object_relations_Icecat  WHERE src_id= :srcId AND dest_id= :destId AND fieldname='videos' AND type='asset'";
                    $relationDelStmt = $db->prepare($relationDeleteSql);
                } catch (\Exception $ex) {
                }
            }
            $rowCounter++;
        }
    }

    public function down(Schema $schema): void
    {
        $na = 'NA';
    }
}
