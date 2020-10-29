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

namespace jason7\huaweiPush;

use jason7\huaweiPush\push_msg\android\AndroidConfig;
use jason7\huaweiPush\push_msg\android\AndroidNotification;
use jason7\huaweiPush\push_msg\android\Badge;
use jason7\huaweiPush\push_msg\android\ClickAction;
use jason7\huaweiPush\push_msg\android\LightSetting;
use jason7\huaweiPush\push_msg\android\LightSettingColor;
use jason7\huaweiPush\push_msg\apns\Alert;
use jason7\huaweiPush\push_msg\apns\ApnsConfig;
use jason7\huaweiPush\push_msg\apns\ApnsHeaders;
use jason7\huaweiPush\push_msg\apns\ApnsHmsOptions;
use jason7\huaweiPush\push_msg\apns\Aps;
use jason7\huaweiPush\push_msg\instanceapp\InstanceAppConfig;
use jason7\huaweiPush\push_msg\notification\Notification;
use jason7\huaweiPush\push_msg\webpush\WebPushConfig;
use jason7\huaweiPush\push_msg\webpush\WebPushHeaders;
use jason7\huaweiPush\push_msg\webpush\WebPushHmsOptions;
use jason7\huaweiPush\push_msg\webpush\WebPushNotification;
use jason7\huaweiPush\push_msg\webpush\WebPushNotificationAction;
use jason7\huaweiPush\push_msg\instanceapp\InstanceAppPushbody;
use jason7\huaweiPush\push_msg\instanceapp\InstanceAppRingtone;
use jason7\huaweiPush\push_msg\PushMessage;

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

    public function __construct($app_id,$app_secret)
    {
        $this->appsecret = $app_secret;
        $this->appid = $app_id;
        $this->hw_token_server = "https://oauth-login.cloud.huawei.com/oauth2/v2/token";
        $this->hw_push_server = "https://push-api.cloud.huawei.com/v1/{app_id}/messages:send";
    }

    public function sendPushMsgMessageByMsgType($msg_type, $topic = "")
    {
        $application_server = $this->hw_push_server;

        $this->push_msg_type = $msg_type;
        $message = $this->getMessageByMsgType($msg_type);

        $application = $this->createApplication($application_server);

        $application->push_send_msg($message->getFields());
    }

    public function sendPushMsgRealMessage($pushData)
    {
        $application_server = $this->hw_push_server;
        $application = $this->createApplication($application_server);

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

        $application->push_send_msg($message);
    }


    private function getDefaultAndroidNotificationContent($titel)
    {
        $prefixTitleData = '';
        switch ($this->push_msg_type) {
            case Constants::PUSHMSG_NOTIFICATION_MSG_TYPE:
                {
                    $prefixTitleData = ' notification ';
                    break;
                }
            case Constants::PUSHMSG_PASS_THROUGHT_MSG_TYPE:
                {
                    $prefixTitleData = ' passthrough ';
                    break;
                }

            case Constants::PUSHMSG_FASTAPP_MSG_TYPE:
                {
                    $prefixTitleData = ' fastapp ';
                    break;
                }
            case Constants::PUSHMSG_TOPIC_MSG_TYPE:
                {
                    $prefixTitleData = ' topic ';
                    break;
                }
            case Constants::PUSHMSG_CONDITION_MSG_TYPE:
                {
                    $prefixTitleData = ' condition ';
                    break;
                }

            case Constants::APN_MSG_TYPE:
                {
                    $prefixTitleData = ' apn ';
                    break;
                }
            case Constants::WEB_PUSH_MSG_TYPE:
                {
                    $prefixTitleData = ' webpush ';
                    break;
                }
        }

        return $prefixTitleData . $titel . $prefixTitleData;
    }

    private function createAndroidNotification()
    {
        // generate click_action msg body for android notification-3-click_action
        $click_action = new ClickAction();
        $click_action->type(2);
        $click_action->type(1);

        $click_action->intent("#Intent;compo=com.rvr/.Activity;S.W=U;end");
        $click_action->action("test add");
        $click_action->url("https://www.baidu.com");
        $click_action->rich_resource("test rich resource");
        $click_action->buildFields();

        // generate Badge for android notification-3-badge
        $badge = new Badge();
        $badge->add_num(99);
        $badge->setclass("Classic");
        $badge->set_num(99);
        $badge->buildFields();

        // generate Light Settings for android notification-3-badge
        $lightSetting = new LightSetting();
        $lightSetting->light_on_duration("3.5");
        $lightSetting->light_off_duration("5S");
        // set light setting color
        $LightSettingColor = new LightSettingColor();
        $LightSettingColor->setgenFullcolor(0, 0, 1, 1);
        $LightSettingColor->buildFields();
        $lightSetting->color($LightSettingColor->getFields());
        $lightSetting->buildFields();

        // 构建android notification消息体-2 for android config
        $android_notification = new AndroidNotification();
        $android_notification->title($this->getDefaultAndroidNotificationContent("default hw title "));
        $android_notification->body($this->getDefaultAndroidNotificationContent("default hw body"));
        $android_notification->icon("https://res.vmallres.com/pimages//common/config/logo/SXppnESYv4K11DBxDFc2.png");
        $android_notification->color("#AACCDD");
        $android_notification->sound("https://att.chinauui.com/day_120606/20120606_7fcf2235b44f1eab0b4dadtAkAGMTBHK.mp3");
        $android_notification->tag("tagBoom");
        $android_notification->body_loc_key("M.String.body");
        $android_notification->body_loc_args([
            "Boy",
            "Dog"
        ]);
        $android_notification->title_loc_key("M.String.title");
        $android_notification->title_loc_args([
            "Girl",
            "Cat"
        ]);
        $android_notification->channel_id("RingRing");
        $android_notification->notify_summary("Some Summary");
        $android_notification->image("https://developer-portalres-drcn.dbankcdn.com/system/modules/org.opencms.portal.template.core/resources/images/icon_Promotion.png");
        $android_notification->style(0);
        $android_notification->big_title("Big Boom Title");
        $android_notification->big_body("Big Boom Body");
        $android_notification->auto_clear(86400000);
        $android_notification->notify_id(486);
        $android_notification->group("Espace");
        $android_notification->importance(NotificationPriority::NOTIFICATION_PRIORITY_NORMAL);
        $android_notification->ticker("i am a ticker");
        $android_notification->auto_cancel(false);
        $android_notification->when("2019-11-05");
        $android_notification->use_default_vibrate(true);
        $android_notification->use_default_light(false);
        $android_notification->visibility("PUBLIC");
        $android_notification->foreground_show(true);
        $android_notification->vibrate_config([
            "1.5",
            "2.000000001",
            "3"
        ]);
        $android_notification->click_action($click_action->getFields());
        $android_notification->badge($badge->getFields());
        $android_notification->light_settings($lightSetting->getFields());

        $android_notification->buildFields();

        return $android_notification;
    }

    private function createAndroidConfig()
    {
        $android_notification = $this->createAndroidNotification();

        $android_config = new AndroidConfig();
        $android_config->collapse_key(- 1);
        $android_config->urgency(AndroidConfigDeliveryPriority::PRIORITY_HIGH);
        $android_config->ttl("1448s");
        $android_config->bi_tag("Trump");
        if ($this->push_msg_type == Constants::PUSHMSG_FASTAPP_MSG_TYPE) {
            $android_config->fast_app_target(1);
        } else {
            $android_config->notification($android_notification->getFields());
        }
        $android_config->buildFields();
        return $android_config;
    }

    private function createNotification()
    {
        $notification = new Notification("Big News", "This is a Big News!", "https://res.vmallres.com/pimages//common/config/logo/SXppnESYv4K11DBxDFc2_0.png");
        $notification->buildFields();
        return $notification;
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

    private function getMessageByMsgType($msg_type)
    {
        switch ($msg_type) {
            case Constants::PUSHMSG_NOTIFICATION_MSG_TYPE:
                {
                    return $this->createNotificationMsg();
                }
            case Constants::PUSHMSG_PASS_THROUGHT_MSG_TYPE:
                {
                    return $this->createPassThroughMsg();
                }

            case Constants::PUSHMSG_FASTAPP_MSG_TYPE:
                {
                    return $this->createFastAppMsg();
                }
            case Constants::PUSHMSG_TOPIC_MSG_TYPE:
                {
                    return $this->createTopicMsg();
                }
            case Constants::PUSHMSG_CONDITION_MSG_TYPE:
                {
                    return $this->createConditionMsg();
                }

            case Constants::APN_MSG_TYPE:
                {
                    return $this->createApnsMsg();
                }
            case Constants::WEB_PUSH_MSG_TYPE:
                {
                    return $this->createWebPushMsg();
                }
        }
    }
    
    private function createFastAppConfigNotificationData(){
        $instanceAppConfig = new InstanceAppConfig();
        $instanceAppConfig->pushtype(0);
        
        $instanceAppPushbody = new InstanceAppPushbody();
        $instanceAppPushbody->title("test fast app");
        $instanceAppPushbody->description("test fast app description");
        $instanceAppPushbody->page("/");
        $instanceAppPushbody->params(array(
            "key1"=>"test1",
            "key2"=>"test2"
        ));
        
        $instanceAppRingtone = new InstanceAppRingtone();
        $instanceAppRingtone->breathLight(true);
        $instanceAppRingtone->vibration(true);
        $instanceAppRingtone->buildFields();
        
        $instanceAppPushbody->ringtone($instanceAppRingtone->getFields());
        $instanceAppPushbody->buildFields();
        
        $instanceAppConfig->pushbody($instanceAppPushbody->getFields());
        $instanceAppConfig->buildFields();
        
        return $instanceAppConfig;
        
    }
    
    private function createFastAppConfigPassThroughData(){
        $instanceAppConfig = new InstanceAppConfig();
        $instanceAppConfig->pushtype(1);
        
        $instanceAppPushbody = new InstanceAppPushbody();
        $instanceAppPushbody->messageId("111110001");
        $instanceAppPushbody->data("hw default passthroug test");
        $instanceAppPushbody->buildFields();
        
        $instanceAppConfig->pushbody($instanceAppPushbody->getFields());
        $instanceAppConfig->buildFields();
        
        return $instanceAppConfig;
        
    }

    private function createFastAppMsg()
    {
        $message = new PushMessage();

        $message->data($this->createFastAppConfigNotificationData()->getFields());

        $message->android($this->createAndroidConfig()
            ->getFields());
 
        $message->token(array(
            $this->fast_push_token
        ));

        $message->buildFields();
        return $message;
    }

    private function createNotificationMsg()
    {
        $message = new PushMessage();

        $message->android($this->createAndroidConfig()
            ->getFields());
        $message->notification($this->createNotification()
            ->getFields());

        $message->token(array(
            $this->hw_push_token_key
        ));

        $message->buildFields();
        return $message;
    }

    private function createTopicMsg()
    {
        $message = new PushMessage();

        $message->android($this->createAndroidConfig()
            ->getFields());
        // $message->notification($this->createNotification()->buildFields());

        $message->topic($this->default_topic);

        $message->buildFields();
        return $message;
    }

    private function createConditionMsg()
    {
        $message = new PushMessage();

        $message->android($this->createAndroidConfig()
            ->getFields());
        $message->condition("'defaultTopic' in topics");

        $message->buildFields();
        return $message;
    }

    private function createPassThroughMsg()
    {
        $message = new PushMessage();

        $message->data("1111");
        $message->token(array(
            $this->hw_push_token_key
        ));

        $message->buildFields();
        return $message;
    }

    private function createApnsMsg()
    {
        $message = new PushMessage();
        $apnsConfig = $this->createApnsConfig();
        $message->apns($apnsConfig->getFields());

        $message->token(array(
            $this->apn_push_token_key
        ));
        $message->buildFields();

        return $message;
    }

    private function createWebPushMsg()
    {
        $message = new PushMessage();

        $message->webpush($this->createWebPush()
            ->getFields());
        $message->token(array(
            $this->webpush_push_token_key
        ));

        PushLogConfig::getSingleInstance()->LogMessage('[' . __CLASS__ . ']' . '[web-token:' . json_encode($message->get_token()) . ']', Constants::HW_PUSH_LOG_DEBUG_LEVEL);

        $message->buildFields();

        return $message;
    }
}