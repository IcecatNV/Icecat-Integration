<?php

namespace IceCatBundle\Services;

use Pimcore\Db;
use Symfony\Component\HttpFoundation\RequestStack;

class LoginService extends InfoService
{
    private $iceCatXmlUrl = 'https://data.icecat.biz/xml_s3/xml_server3.cgi?ean_upc=5397063929863;lang=en;output=productxml';

    /**
     * Method : Authenticates a user on ice-cat
     * and return response accordingly
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->db = Db::get();
        parent::__construct($requestStack);
    }

    public function authApi($username, $password)
    {
        try {
            if (!empty($username) && !empty($password)) {
                $username_password = base64_encode($username . ':' . $password);
                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => $this->iceCatXmlUrl,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_HTTPHEADER => [
                        'Authorization: Basic ' . $username_password
                    ],
                ]);
                $response = curl_exec($curl);
                curl_close($curl);
                $finalResponse = strpos(json_encode(simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA)), 'ErrorMessage') > 0 ? 1 : 0;

                if ($finalResponse == 0) {
                    // user not found
                    $this->saveUser($username);
                    $response = ['status' => 'success', 'message' => 'User found'];
                } else {
                    // user found
                    $response = ['status' => 'error', 'message' => 'User not found'];
                }

                return $response;
            }

            return ['status' => 'error', 'message' => 'User name / password empty'];
        } catch (\Exception $e) {
            $response = ['status' => 'error', 'message' => 'Something went wrong'];

            return $response;
        }
    }

    public function saveUser($userName)
    {
        $pimUserId = \Pimcore\Tool\Admin::getCurrentUser()->getId();
        $sql = 'Select * from ' . self::LOGIN_TABLE . ' where icecat_user_id=' .  $this->db->quote($userName) . ' AND pim_user_id=' . $pimUserId;
        $result = $this->db->fetchAll($sql);
        if (empty($result)) {
            $sql = ' INSERT INTO ' . self::LOGIN_TABLE . ' (icecat_user_id, pim_user_id, login_status, lastactivity_time, creation_time)
             VALUES(' . $this->db->quote($userName) . ", $pimUserId, 1," .  time() . ',' . time() . ')';
        } else {
            $sql = 'UPDATE ' . self::LOGIN_TABLE . ' SET login_status=1' . ' where icecat_user_id=' .  $this->db->quote($userName) . ' AND pim_user_id=' . $pimUserId;
        }
        $this->db->exec($sql);
    }

    public function getLoginStatus()
    {
        $pimUserId = \Pimcore\Tool\Admin::getCurrentUser()->getId();
        $sql = 'SELECT * FROM ' . self::LOGIN_TABLE . ' WHERE login_status=1 AND pim_user_id=' . $pimUserId;
        $result = $this->db->fetchAll($sql);
        if (!empty($result)) {
            return $result[0];
        } else {
            return ['login_status' => 0];
        }
    }

    public function logOutUser()
    {
        $pimUserId = \Pimcore\Tool\Admin::getCurrentUser()->getId();
        $sql = 'UPDATE ' . self::LOGIN_TABLE . ' SET login_status=0' . ' where  pim_user_id=' . $pimUserId;
        $this->db->exec($sql);
        $this->setDataInSession(self::USER_INFO_SESSION_KEY, []);

        return true;
    }
}
