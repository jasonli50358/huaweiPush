<?php
/**
 * Created by PhpStorm.
 * Filename: TopicMsg.php
 * Desc:
 * User: Administrator
 * Date: 2020/10/28
 * Time: 15:10
 */

namespace jason7\huaweiPush;


class TopicMsg
{

    // madatory
    private $topic;

    // madatory
    private $tokenArray;

    private $fields;

    public function __construct()
    {
        $this->fields = array();
    }

    public function topic($value)
    {
        $this->topic = $value;
    }

    public function tokenArray($value)
    {
        $this->tokenArray = $value;
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function buildFields()
    {
        $keys = array(
            'topic',
            'tokenArray'
        );
        foreach ($keys as $key) {
            if (isset($this->$key)) {
                $this->fields[$key] = $this->$key;
            }
        }
    }
}