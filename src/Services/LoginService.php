<?php

namespace IceCatBundle\Services;

use Pimcore\Db;
use Symfony\Component\HttpFoundation\RequestStack;

class LoginService extends InfoService
{


    private const  AUTHURL  = 'https://bo.icecat.biz/restful/v3/Session';


    private const  CHECKPROFILEURL  = 'https://icecat.biz/rest/user-profile';


    private const CIPHERING_VALUE = "AES-128-CTR";

    // Store the encryption key
    private const ENCRYPTION_KEY = "ICECAT78993123u89123";

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
                $curl = curl_init();
                $bodyFields = array(
                    'Login'    => $username,
                    'Password'    => $password,
                    'Session'     => "Rest",
                );
                $bodyJson   = json_encode($bodyFields);

                curl_setopt_array($curl, [
                    CURLOPT_URL => self::AUTHURL,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST  => 'POST',
                    CURLOPT_POSTFIELDS     => $bodyJson,
                    CURLOPT_HTTPHEADER     => ['Content-Type: application/json']
                ]);
                $response = curl_exec($curl);
                curl_close($curl);

                $finalResponse = json_decode($response, true);

                if (isset($finalResponse['Data']) && !empty($finalResponse['Data']['SessionId'])) {
                    $subscriptionData =  $this->checkUserProfile($finalResponse['Data']['SessionId']);
                    if ($subscriptionData['status'] == 'success') {
                        $this->saveUser($username, $password, $finalResponse['Data']['SessionId'], $subscriptionData['subscriptionLevel']);
                        $response = ['status' => 'success', 'message' => 'User found'];
                    } else {
                        $response = ['status' => 'error', 'message' => 'User not found'];
                    }
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



    public function checkUserProfile($sessionId)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => self::CHECKPROFILEURL . '?AccessKey=' . $sessionId,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json']
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        $finalResponse = json_decode($response, true);
        if (isset($finalResponse['Data']) && !empty($finalResponse['Data']['SubscriptionLevel'])) {
            $response = ['status' => 'success', 'subscriptionLevel' => $finalResponse['Data']['SubscriptionLevel']];
        } else {
            // user found
            $response = ['status' => 'error', 'message' => 'User not found'];
        }

        return $response;
    }

    public function saveUser($userName, $password, $sessionId, $subscriptionLevel)
    {
        $pimUserId = \Pimcore\Tool\Admin::getCurrentUser()->getId();
        $sql = 'Select * from ' . self::LOGIN_TABLE . ' where icecat_user_id=' .  $this->db->quote($userName) . ' AND pim_user_id=' . $pimUserId;
        $result = $this->db->fetchAll($sql);
        if (empty($result)) {
            $sql = ' INSERT INTO ' . self::LOGIN_TABLE . ' (icecat_user_id, pim_user_id, login_status, lastactivity_time, creation_time,session_id,subscription_level,icecat_password)
             VALUES(' . $this->db->quote($userName) . ", $pimUserId, 1," .  time() . ',' . time() . ',' . $this->db->quote($sessionId) . ',' . $this->db->quote($subscriptionLevel) . ',' . $this->db->quote(base64_encode($password)) . ')';
        } else {
            $sql = 'UPDATE ' . self::LOGIN_TABLE . ' SET login_status=1,session_id=' .  $this->db->quote($sessionId) . ',subscription_level = ' . $this->db->quote($subscriptionLevel) . ',icecat_password = ' . $this->db->quote(base64_encode($password)) . 'where icecat_user_id=' .  $this->db->quote($userName) . ' AND pim_user_id=' . $pimUserId;
        }
        $this->db->exec($sql);
    }

    public function getLoginStatus()
    {
        $pimUserId = \Pimcore\Tool\Admin::getCurrentUser()->getId();
        $sql = 'SELECT * FROM ' . self::LOGIN_TABLE . ' WHERE login_status=1 AND pim_user_id=' . $pimUserId;
        $result = $this->db->fetchAll($sql);
        if (!empty($result)) {
            $this->authApi($result[0]['icecat_user_id'], base64_decode($result[0]['icecat_password']));
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
