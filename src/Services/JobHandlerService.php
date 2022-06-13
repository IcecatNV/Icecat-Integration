<?php

namespace IceCatBundle\Services;

class JobHandlerService
{
    const JOB_DATA_CONTAINER_TABLE = 'ice_cat_processes';

    public function makeJobEntry($pimUserId, $icecatUserName, $fileName, $fileExtension, $languages = 'en')
    {
        $db = \Pimcore\Db::get();
        $jobId = uniqid();
        $languageCount = count($this->languages = explode('|', $languages));
        $insertQuery = 'INSERT INTO ' . self::JOB_DATA_CONTAINER_TABLE . "(jobid,pimcore_user_id,icecat_user_name,filename,file_extension,status,languages,total_languages)
                        VALUES ('$jobId',$pimUserId,'$icecatUserName','$fileName','$fileExtension','new','$languages','$languageCount')";
        $result = $db->exec($insertQuery);
        $db->close();

        return $jobId;
    }

    public function getNewJobs()
    {
        $db = \Pimcore\Db::get();
        $query = 'SELECT * FROM  ' . self::JOB_DATA_CONTAINER_TABLE . " WHERE status = 'new'";
        $result = $db->fetchAll($query);

        return $result;
    }

    public function getJobById($jobId)
    {
        $db = \Pimcore\Db::get();
        $query = 'SELECT * FROM  ' . self::JOB_DATA_CONTAINER_TABLE . " WHERE status = 'new' || status = 'fetching'";
        $result = $db->fetchAll($query);

        return $result;
    }

    public function updateCurrentJob($query)
    {
        $db = \Pimcore\Db::get();
        $result = $db->exec($query);

        return $result;
    }

    public function isLive($jobId)
    {
        $db = \Pimcore\Db::get();
        $query = 'SELECT * FROM  ' . self::JOB_DATA_CONTAINER_TABLE . " WHERE jobid = '$jobId'";
        $result = $db->fetchRow($query);
        if (empty($result)) {
            return false;
        }
        if ($result['completed'] == 1) {
            return false;
        }

        return true;
    }
}
