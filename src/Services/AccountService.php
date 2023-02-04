<?php

namespace IceCatBundle\Services;

use Pimcore\Db;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class AccountService
{
    const LOGIN_TABLE = 'icecat_user_login';
    protected $request;
    const USER_INFO_SESSION_KEY = 'session_iceCatUserInfo';
    const SELECTED_GTIN_INFO_SESSION_KEY = 'session_iceCatNotToImportGtins';
    const ICE_CAT_PROCESSES_TABLE = 'ice_cat_processes';
    const DATA_IMPORT_TABLE = 'icecat_imported_data';
    const JOB_FINISHED_STATUSES = [
        'completed', 'failed'
    ];


    protected $db;

    protected $infoService;

    public function __construct(InfoService $infoService)
    {
        $this->infoService  = $infoService;
    }


    /**
     * Method : Updates user account data such as contentoken
     *
     * @param array $data
     * @return array
     */
    public function updateUserInfo($pimUserId, array $data)
    {
        $accessToken = $data['accessToken'] ?? null;
        $appkey =  $data['appKey'] ?? null;
        $contentToken =  $data['contentToken'] ?? null;
        $db = \Pimcore\Db::get();
        $sql = 'UPDATE ' . self::LOGIN_TABLE . ' SET access_token=' . $db->quote($accessToken) . ',content_token=' . $db->quote($contentToken) . ',app_key=' . $db->quote($appkey) . ' where  icecat_user_id=' . $db->quote($pimUserId);
        $db->exec($sql);
    }
}