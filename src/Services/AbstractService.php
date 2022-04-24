<?php

namespace IceCatBundle\Services;

use Pimcore\Db;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class AbstractService
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

    public function __construct(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
        $this->db = Db::get();
    }

    public static $fileUploadPath = PIMCORE_PRIVATE_VAR . '/ice-cat';

    public function getObjectCreationProcess($jobId)
    {
        $sql = 'SELECT * FROM ' . self::ICE_CAT_PROCESSES_TABLE . ' WHERE processed_records is not null AND jobid=' . $this->db->quote($jobId);
        $result = $this->db->fetchAll($sql);

        return $result;
    }

    public function checkFilePathExist($path, $create = false)
    {
        $exist = is_dir($path);
        if ($create && !$exist) {
            mkdir($path, 0777, true);
            $exist = is_dir($path);
        }

        return $exist;
    }

    public function setNotToImportGtinsInSession(Request $request, $gtins, $page)
    {
        $session = $request->getSession();
        try {
            $bag = (array)$session->get(self::SELECTED_GTIN_INFO_SESSION_KEY);
        } catch (\Exception $ex) {
            $session->set(self::SELECTED_GTIN_INFO_SESSION_KEY, []);
        }

        if (empty($bag) || empty($bag['gtins'])) {
            $bag['gtins'] = [$page => implode(',', $gtins), 'all' => implode(',', $gtins)];
        } else {
            $storedGtins = $bag['gtins'];
            $all = explode(',', $storedGtins['all']);
            $all = array_merge($all, $gtins);
            $storedGtins['all'] = implode(',', array_unique($all));
            $storedGtins[$page] = implode(',', $gtins);
            $bag['gtins'] = $storedGtins;
        }

        $session->set(self::SELECTED_GTIN_INFO_SESSION_KEY, $bag);
    }

    public function getDataFromSession($key)
    {
        $session = $this->request->getSession();

        return $session->get($key);
    }

    public function setDataInSession($key, $data)
    {
        $session = $this->request->getSession();

        return $session->set($key, $data);
    }

    public function getRunningJob()
    {
        $sql = 'SELECT * FROM ' . self::ICE_CAT_PROCESSES_TABLE . ' WHERE completed=0 order by created_at desc';
        $result = $this->db->fetchAll($sql);
        if (empty($result)) {
            return false;
        }

        return $result[0];
    }

    public function getFetchingProcess($jobId)
    {
        $sql = 'SELECT * FROM ' . self::ICE_CAT_PROCESSES_TABLE . ' WHERE fetched_records is not null and jobid='  . $this->db->quote($jobId);
        $result = $this->db->fetchAll($sql);

        return $result;
    }

    public function getTotalFetchedProducts($jobId)
    {
        $sql = 'SELECT count(id) as fetched_records FROM ' . self::DATA_IMPORT_TABLE . ' WHERE  is_product_found = 1 and job_id='  . $this->db->quote($jobId);
        $result = $this->db->fetchRow($sql);

        return $result;
    }

    public function getInfoForUnfoundProducts($jobId)
    {
        $sql = 'SELECT * FROM ' . self::DATA_IMPORT_TABLE . ' WHERE is_product_found = 0 and  job_id='  . $this->db->quote($jobId);
        $result = $this->db->fetchAll($sql);

        return $result;
    }

    public function checkIfFetchingDone($jobId)
    {
        $sql = 'SELECT * FROM ' . self::ICE_CAT_PROCESSES_TABLE . " WHERE status is not null and  status != 'fetching' AND jobid="  . $this->db->quote($jobId);
        $result = $this->db->fetchAll($sql);
        if (empty($result)) {
            return false;
        }

        return true;
    }

    public function getUserInfo()
    {
        //        p_r(\Pimcore\Tool\Admin::getCurrentUser());die;
        if ((\Pimcore\Tool\Admin::getCurrentUser())) {
            $pimUserId = (\Pimcore\Tool\Admin::getCurrentUser())->getId();
        } else {
            $pimUserId = $this->getDataFromSession('pimUserId');
        }
        $sql = 'SELECT * FROM ' . self::LOGIN_TABLE . ' WHERE login_status=1 AND pim_user_id=' . $pimUserId;
        $result = $this->db->fetchAll($sql);
        if (!empty($result)) {
            return $result[0];
        } else {
            return ['login_status' => false];
        }
    }

    public function getIfAnyUploadDone()
    {
        $sql = 'SELECT * FROM ' . self::ICE_CAT_PROCESSES_TABLE . ' WHERE completed=0';
        $result = $this->db->fetchAll($sql);
        if (empty($result)) {
            return false;
        }

        return true;
    }

    public function getIfAnyFetchingProcess($jobId)
    {
        $sql = 'SELECT * FROM ' . self::ICE_CAT_PROCESSES_TABLE . " WHERE (status = 'fetched' OR status='fetching') AND jobid=" . $this->db->quote($jobId);
        $result = $this->db->fetchAll($sql);
        if (!empty($result)) {
            //            return ['fetching' => true, 'jobId' => $result[0]['jobid'],
            //                'IceCatUser' => $result[0]['icecat_user_name'],
            //                'started' => $result[0]['starting_dateTime']
            //            ];
            return true;
        }

        return false;
        //        return ['fetching' => false];
    }

    public function getIfAnyObjectCreationProcess($jobId)
    {
        $result = $this->getObjectCreationProcess($jobId);
        if (!empty($result)) {
            return true;
        }

        return false;
    }

    public function getFetchingProcessId()
    {
        $result = $this->getFetchingProcess();
        if (empty($result)) {
            return false;
        }

        return $result[0]['jobid'];
    }

    public function emptySelectedGtinsFromSession()
    {
        $this->request->getSession()->set(self::SELECTED_GTIN_INFO_SESSION_KEY, []);
    }

    public function emptyUserInfoFromSession()
    {
        $this->request->getSession()->set(self::USER_INFO_SESSION_KEY, []);
    }

    public function saveUserInfoInSession($info)
    {
        $this->request->getSession()->set(self::USER_INFO_SESSION_KEY, $info);
    }

    public function emptyIceCatSession()
    {
        $this->emptySelectedGtinsFromSession();
        $this->emptyUserInfoFromSession();
    }

    public function getNotToImportGtinsFromSession($page = 0)
    {
        $session = $this->request->getSession();
        try {
            $bag = $session->get(self::SELECTED_GTIN_INFO_SESSION_KEY);
        } catch (\Exception $ex) {
            return '';
        }

        if (empty($bag) || empty($bag['gtins'])) {
            return '';
        }
        $storedGtins = $bag['gtins'];
        if ($page == 'all') {
            return $storedGtins['all'];
        }

        return isset($storedGtins[$page]) ? $storedGtins[$page] : '';
    }
}
