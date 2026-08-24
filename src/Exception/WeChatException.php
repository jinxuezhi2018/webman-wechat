<?php

namespace xuezhitech\wechat\Exception;

use Exception;

class WeChatException extends Exception
{
    /**
     * @var int 微信错误码
     */
    protected int $wechatErrCode;

    /**
     * @var string 微信错误信息
     */
    protected string $wechatErrMsg;

    /**
     * @param string $message
     * @param int $wechatErrCode
     * @param string $wechatErrMsg
     */
    public function __construct(string $message, int $wechatErrCode = 0, string $wechatErrMsg = '')
    {
        $this->wechatErrCode = $wechatErrCode;
        $this->wechatErrMsg = $wechatErrMsg;
        parent::__construct($message, $wechatErrCode);
    }

    /**
     * 获取微信错误码
     *
     * @return int
     */
    public function getWechatErrCode(): int
    {
        return $this->wechatErrCode;
    }

    /**
     * 获取微信错误信息
     *
     * @return string
     */
    public function getWechatErrMsg(): string
    {
        return $this->wechatErrMsg;
    }
}
