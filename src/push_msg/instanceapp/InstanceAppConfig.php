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
 * function: ApnsConfig => =>PushMessage(apns) for ios channel
 */
namespace jasonli50358\huaweiPush\push_msg\instanceapp;

use jasonli50358\huaweiPush\PushLogConfig;
use jasonli50358\huaweiPush\Constants;

class InstanceAppConfig
{
    //push_type 0:notification;1,pass-through
    private $pushtype;
    private $pushbody;
    private $fields;

    public function __construct()
    {
    }

    public function pushtype($value)
    {
        $this->pushtype = $value;
    }
    public function pushbody($value)
    {
        $this->pushbody = $value;
    }
   
    public function getFields()
    {
        $result = "{";
        foreach ($this->fields as $key=>$value) {
            $result = $result .$key.":".json_encode($value).",";
            PushLogConfig::getSingleInstance()->LogMessage('[' . __CLASS__ . '][result:' .$result, Constants::HW_PUSH_LOG_DEBUG_LEVEL);
        }
        if (strlen($result) > 1){
            $result = rtrim($result, ",");
        }
        $result = $result."}";
        return $result;
    }

    public function buildFields()
    {

        $keys = array(
            'pushtype',
            'pushbody'
        );
        foreach ($keys as $key) {
            PushLogConfig::getSingleInstance()->LogMessage('[' . __CLASS__ . '][key:' . $key . '][value:' . json_encode($this->$key) . ']', Constants::HW_PUSH_LOG_DEBUG_LEVEL);
            if (isset($this->$key)) {
                $this->fields[$key] = $this->$key;
            }
        }
        PushLogConfig::getSingleInstance()->LogMessage('[' . __CLASS__ . '][buildFields result:' . json_encode($this->fields), Constants::HW_PUSH_LOG_DEBUG_LEVEL);
       
    }

}

