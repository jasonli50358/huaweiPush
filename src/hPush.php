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

namespace jasonli50358\huaweiPush;


class hPush
{
    // ordinal app
    private $appid;
    private $appsecret;
    // FOR PUSH MSG NOTIFICATION,PASSTHROUGH TOPIC/TOKEN/CONDITION
    public  $hw_push_token_key;
    // FOR APN
    public $apn_push_token_key;    
    // FOR WEBPUSH
    public $webpush_push_token_key;
    
    // fast app
    private $fast_appid;
    private $fast_appsecret;
    // fast app token
    public  $fast_push_token;

    private $hw_token_server;
    private $hw_push_server;
    private $push_msg_type;
    private $default_topic = 'defaultTopic';

    private $str_len = 35;
    /**
     * @var Application
     */
    private $application;

    public function __construct($app_id,$app_secret)
    {
        $this->appsecret = $app_secret;
        $this->appid = $app_id;
        $this->hw_token_server = "https://oauth-login.cloud.huawei.com/oauth2/v2/token";
        $this->hw_push_server = "https://push-api.cloud.huawei.com/v1/{app_id}/messages:send";
        $this->application = $this->createApplication($this->hw_push_server);
    }

    /**
     * 获取AccessToken
     * @return array|null
     */
    public function getAccessToken()
    {
        return $this->application->refresh_token();
    }

    /**
     * 设置accessToken
     * @param $token
     */
    public function setAccessToken($token)
    {
        $this->application->setAccessToken($token);
    }

    public function sendPushMsgRealMessage($pushData)
    {
        $message = [
            'notification'=>[
                'title'=>$pushData['title'],
                'body'=>$pushData['content'],
            ],
            'android'=>[
                'notification'=>[
                    'click_action'=>[
                        'type'=>1,
                        'intent'=>'',
                    ]
                ]
            ],
            'token'=>[],
        ];
        if (is_array($pushData['reg_ids'])) {
            $message['token'] = $pushData['reg_ids'];
        }else{
            $message['token'] = [$pushData['reg_ids']];
        }
        $intent = 'intent://com.huawei.pushparse/hwpushdeeplink?#Intent;scheme=hwpushscheme;';
        if (isset($pushData['extra']) && !empty($pushData['extra'])) {
            $pushExtra = json_encode($pushData['extra']);
            $message['android']['notification']['click_action']['intent'] = $intent.'S.pushExtra='.$pushExtra.';end';
        }else{
            $message['android']['notification']['click_action']['intent'] = $intent.';end';
        }

        return $this->application->push_send_msg($message);
    }


    private function createApplication($application_server)
    {
        if ($this->push_msg_type == Constants::PUSHMSG_FASTAPP_MSG_TYPE){
            $application = new Application($this->fast_appid, $this->fast_appsecret, $this->hw_token_server, $application_server);
            return $application;
        }
        $application = new Application($this->appid, $this->appsecret, $this->hw_token_server, $application_server);
        return $application;
    }
}