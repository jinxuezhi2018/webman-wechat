<?php

namespace xuezhitech\wechat;

use xuezhitech\wechat\Auth\Code2Session;

/**
 * 微信 SDK 主类
 *
 * @example
 * $wechat = new WeChat([
 *     'app_id' => 'your_app_id',
 *     'app_secret' => 'your_app_secret',
 * ]);
 * $result = $wechat->auth->code2Session($code);
 */
class WeChat
{
    /**
     * @var array 配置信息
     */
    protected array $config;

    /**
     * @var Code2Session
     */
    public Code2Session $auth;

    /**
     * @param array $config 配置数组
     * @param string $config['app_id'] 小程序 appId
     * @param string $config['app_secret'] 小程序 appSecret
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->auth = new Code2Session($config);
    }

    /**
     * 获取配置
     *
     * @param string|null $key
     * @return mixed
     */
    public function getConfig(?string $key = null): mixed
    {
        if ($key === null) {
            return $this->config;
        }
        return $this->config[$key] ?? null;
    }
}
