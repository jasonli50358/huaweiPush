<?php
/**
 * Created by PhpStorm.
 * Filename: TopicCommonSample.php
 * Desc:
 * User: Administrator
 * Date: 2020/10/28
 * Time: 15:10
 */

namespace jasonli50358\huaweiPush;


class TopicCommonSample
{

    private $topic = "defaultTopic";

    private $topic_msg_create_type = 1;

    private $appsecret;

    private $appid;

    private $hw_token_server;

    private $hw_topic_subscriber_server;

    private $hw_topic_unsubscriber_server;

    private $hw_topic_query_subscriber_server;

    private $tokenServerKey;

    private $log_suffix_show = ".............................";

    public function __construct($topic_value = "", $default_msg_create_type = "")
    {
        if (! empty($topic_value)) {
            $this->topic = $topic_value;
        }
        if (! empty($default_msg_create_type)) {
            $this->topic_msg_create_type = $default_msg_create_type;
        }

        $pushConfig = PushConfig::getSingleInstance();

        $this->appsecret = $pushConfig->HW_APPSECRET;
        $this->appid = $pushConfig->HW_APPID;
        $this->hw_token_server = $pushConfig->HW_TOKEN_SERVER;
        $this->tokenServerKey = $pushConfig->HW_PUSH_TOKEN_ARR;

        $this->hw_topic_subscriber_server = $pushConfig->HW_TOPIC_SUBSCRIBE_SERVER;
        $this->hw_topic_unsubscriber_server = $pushConfig->HW_TOPIC_UNSUBSCRIBE_SERVER;
        $this->hw_topic_query_subscriber_server = $pushConfig->HW_TOPIC_QUERY_SUBSCRIBER_SERVER;
    }

    private function createTopicData()
    {
        $topicMsg = new TopicMsg();
        $topicMsg->topic($this->topic);
        $topicMsg->tokenArray(array(
            $this->tokenServerKey
        ));
        $topicMsg->buildFields();

        return $topicMsg;
    }

    private function createApplication($application_server)
    {
        $application = new Application($this->appid, $this->appsecret, $this->hw_token_server, $application_server);
        return $application;
    }

    private function printLogMethodOperate($msg_type, $dataFlow, $functionName = "", $logLevel = "")
    {
        $dataFlow = 'subscribe topic ' . $dataFlow;
        $logModule = Constants::HW_PUSH_LOG_TOPIC_SUBSCRIBE_MODULE;
        switch ($msg_type) {
            case Constants::TOPIC_UNSUBSCRIBE_MSG_TYPE:
                {
                    $dataFlow = 'unsubscribe topic' . $dataFlow;
                    $logModule = Constants::HW_PUSH_LOG_TOPIC_UNSUBSCRIBE_MODULE;
                }
                break;
        }
        if (empty($logLevel)) {
            $logLevel = Constants::HW_PUSH_LOG_INFO_LEVEL;
        }

        if (empty($functionName)) {
            PushLogConfig::getSingleInstance()->LogMessage('[' . __CLASS__ . ']' . $dataFlow . $this->log_suffix_show, $logLevel, $logModule);
        } else {
            PushLogConfig::getSingleInstance()->LogMessage('[' . __CLASS__ . ']' . '[' . $functionName . ']' . $dataFlow . $this->log_suffix_show, $logLevel, $logModule);
        }
    }

    private function printLogMsgOperate($msg_type, $dataFlow, $functionName = "", $logLevel = "")
    {
        $logModule = Constants::HW_PUSH_LOG_TOPIC_SUBSCRIBE_MODULE;
        switch ($msg_type) {
            case Constants::TOPIC_UNSUBSCRIBE_MSG_TYPE:
                {
                    $logModule = Constants::HW_PUSH_LOG_TOPIC_UNSUBSCRIBE_MODULE;
                }
                break;
        }
        if (empty($logLevel)) {
            $logLevel = Constants::HW_PUSH_LOG_INFO_LEVEL;
        }

        if (empty($functionName)) {
            PushLogConfig::getSingleInstance()->LogMessage('[' . __CLASS__ . ']' . $dataFlow . $this->log_suffix_show, $logLevel, $logModule);
        } else {
            PushLogConfig::getSingleInstance()->LogMessage('[' . __CLASS__ . ']' . '[' . $functionName . ']' . $dataFlow . $this->log_suffix_show, $logLevel, $logModule);
        }
    }

    /**
     * topic subscribe/unsubscribe
     */
    function sendTopicMessage($msg_type)
    {
        $this->printLogMethodOperate($msg_type, "start", __FUNCTION__ . ':' . __LINE__);
        $topicMsg = $this->createTopicData();
        if ($this->topic_msg_create_type == 1) {
            $this->printLogMsgOperate($msg_type, "topicMsg:" . json_encode($topicMsg->getFields()), __FUNCTION__ . ':' . __LINE__, Constants::HW_PUSH_LOG_DEBUG_LEVEL);
        }

        $application_server = $this->hw_topic_subscriber_server;
        if ($msg_type == Constants::TOPIC_UNSUBSCRIBE_MSG_TYPE) {
            $application_server = $this->hw_topic_unsubscriber_server;
        } else if ($msg_type == Constants::TOPIC_SUBSCRIBE_QUERY_MSG_TYPE) {
            $application_server = $this->hw_topic_query_subscriber_server;
            $topicMsg = array(
                'token' => $this->tokenServerKey
            );
        }
        $application = $this->createApplication($application_server);
        $this->printLogMsgOperate($msg_type, "application server:" . json_encode($application->getApplicationFields()), __FUNCTION__ . ':' . __LINE__, Constants::HW_PUSH_LOG_DEBUG_LEVEL);

        $topicResult = "";
        if ($msg_type == Constants::TOPIC_SUBSCRIBE_QUERY_MSG_TYPE) {
            $topicResult = $application->common_send_msg($topicMsg);
        } else {
            $topicResult = $application->common_send_msg($topicMsg->getFields());
        }

        $this->printLogMethodOperate($msg_type, "end", __FUNCTION__ . ':' . __LINE__);
        return $topicResult;
    }
}