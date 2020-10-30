<?php
/**
Copyright 2020. Huawei Technologies Co., Ltd. All rights reserved.

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
 */

/**
 * function: Application support ability of push msg:
 *           push_send_msg   => push msg
 *           common_send_msg => topic msg
 */
namespace jasonli50358\huaweiPush;

use jasonli50358\huaweiPush\Constants;
use jasonli50358\huaweiPush\PushLogConfig;

class Application
{

    private $appid;

    private $appsecret;

    private $token_expiredtime;


    private $validate_only;

    private $hw_token_server;

    private $hw_push_server;

    private $fields;
    /**
     * @var null
     */
    private $accesstoken = null;


    public function __construct($appid, $appsecret, $hw_token_server, $hw_push_server)
    {
        $this->appid = $appid;
        $this->appsecret = $appsecret;
        $this->hw_token_server = $hw_token_server;
        $this->hw_push_server = $hw_push_server;
        $this->token_expiredtime = null;
        $this->validate_only = false;
    }

    public function appid($value)
    {
        $this->appid = $value;
    }

    public function appsecret($value)
    {
        $this->appsecret = $value;
    }

    /**
     * 
     */
    public function validate_only($value)
    {
        $this->validate_only = $value;
    }

    public function getApplicationFields()
    {
        $keys = [
            'appid',
            'appsecret',
            'hw_token_server',
            'hw_push_server',
            'validate_only',
            'accesstoken',
            'token_expiredtime'
        ];
        foreach ($keys as $key) {
            if (isset($this->$key)) {
                $this->fields[$key] = $this->$key;
            }
        }

        return $this->fields;
    }

    private function printLogMethodOperate($dataFlow, $functionName = "", $logLevel = Constants::HW_PUSH_LOG_INFO_LEVEL)
    {
        if (empty($functionName)) {
            PushLogConfig::getSingleInstance()->LogMessage('[' . __CLASS__ . ']' . $dataFlow, $logLevel);
        } else {
            PushLogConfig::getSingleInstance()->LogMessage('[' . __CLASS__ . ']' . '[' . $functionName . ']' . $dataFlow, $logLevel);
        }
    }

    /**
     * 设置token
     * @param $token
     */
    public function setAccessToken($token)
    {
        $this->accesstoken = $token;
    }

    /**
     * 刷新access_token
     * @return array|null
     */
    public function refresh_token()
    {
        $result = json_decode($this->curl_https_post($this->hw_token_server, http_build_query([
            "grant_type" => "client_credentials",
            "client_secret" => $this->appsecret,
            "client_id" => $this->appid
        ]),[
            "Content-Type: application/x-www-form-urlencoded;charset=utf-8"
        ]),true);
        if (empty($result) || ! isset($result['access_token'])) {
            return null;
        }

        return $result;
    }

    private function curl_https_post($url, $data = array(), $header = array())
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        // resolve SSL: no alternative certificate subject name matches target host name
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // check verify
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_POST, 1); // regular post request
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data); // Post submit data


        $ret = @curl_exec($ch);
        if ($ret === false) {
            return null;
        }

        $info = curl_getinfo($ch);

        curl_close($ch);

        return $ret;
    }

    /**
     * push_send_msg for push msg
     */
    public function push_send_msg($msg)
    {
        $body = array(
            "validate_only" => $this->validate_only,
            "message" => $msg
        );

        if (empty($this->accesstoken)){
            return null;
        }

        $result = json_decode($this->curl_https_post(str_replace('{appid}', $this->appid, $this->hw_push_server), json_encode($body), [
            "Content-Type: application/json",
            "Authorization: Bearer {$this->accesstoken}"
        ] // Use bearer auth
        ));

        return json_decode(json_encode($result), true);
    }

    /**
     * common_send_msg for topic msg/other
     */
    public function common_send_msg($msg)
    {
        if ($this->is_token_expired()) {
            $this->refresh_token();
        }
        
        if (empty($this->accesstoken)){
            return null;
        }

        $result = json_decode($this->curl_https_post(str_replace('{appid}', $this->appid, $this->hw_push_server), json_encode($msg), array(
            "Content-Type: application/json",
            "Authorization: Bearer {$this->accesstoken}"
        ) // Use bearer auth
        ));

        if (! empty($result)) {
            $arrResult = json_decode(json_encode($result), true);
            if (isset($arrResult["code"]) && $arrResult["code"] != "80000000") {
                $this->printLogMethodOperate("push_send_msg leave,result:" . json_encode($result), __FUNCTION__ . ':' . __LINE__, Constants::HW_PUSH_LOG_WARN_LEVEL);
            }
        }

        return $result;
    }
}
