<?php


namespace IceCatBundle\Services;


class InfoService extends AbstractService
{

    private $user;
    private $iceCatUserId;
    private $pimUserId;
    private $runningJobId;
    private $anyRunningJobExists;

    const USER_INFO_SESSION_KEY = 'session_iceCatUserInfo';

    public function setIceCatInfoInSession()
    {
        $runningJobInfo = $this->getRunningJob();

        $data['uploadExist'] = false;
        $data['fetchingProcessExist'] = false;
        $data['fetchingProcessInfo'] = [];
        $data['objectCreationProcessExist'] = false;
        $data['objectCreationProcessInfo'] = [];
        $data['jobId'] = false;

        if ($runningJobInfo) {
            $jobId = $runningJobInfo['jobid'];
            $data = [];
            $data['uploadExist'] = true;
            $data['fetchingProcessExist'] = $this->getIfAnyFetchingProcess($jobId);
            $data['objectCreationProcessExist'] = $this->getIfAnyObjectCreationProcess($jobId);
            $data['jobId'] = $jobId;
        }
        $data['user'] = $this->getUserInfo();
        if ($this->getPimUser()) {
            $this->setDataInSession('pimUserId', $this->getPimUserId());
        }
        $this->saveUserInfoInSession($data);
    }

    public function getPimUser()
    {
        return \Pimcore\Tool\Admin::getCurrentUser();
    }

    public function getIceCatUser()
    {

        return $this->getOtherInfo()['user'];
    }

    public function getPimUserId()
    {

        return $this->getPimUser()->getId();
    }

    public function getIceCatUserId()
    {
        $iceCatUser = $this->getIceCatUser();
        if ($iceCatUser['login_status']) {
            return $iceCatUser['icecat_user_id'];
        }
        return false;
    }

    public function anyRunningProcessExist()
    {
        $info = $this->getOtherInfo();
        return $info['jobId'] ? true : false;

    }

    public function resetOtherInfo()
    {
        $this->setDataInSession(self::USER_INFO_SESSION_KEY, []);
    }
    public function getRunningJobId()
    {
        $info = $this->getOtherInfo();
        return $info['jobId'] ? $info['jobId'] : false;
    }

    public function getOtherInfo($force=false)
    {
        if (!$force && !empty($this->getDataFromSession(self::USER_INFO_SESSION_KEY))) {
            return $this->getDataFromSession(self::USER_INFO_SESSION_KEY);
        }
        $this->setIceCatInfoInSession();
        return $this->getDataFromSession(self::USER_INFO_SESSION_KEY);
    }


}
