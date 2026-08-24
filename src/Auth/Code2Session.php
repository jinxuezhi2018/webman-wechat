<?php

namespace xuezhitech\wechat\Auth;

use GuzzleHttp\Client;
use xuezhitech\wechat\Exception\WeChatException;

/**
 * 小程序登录凭证校验（auth.code2Session）
 *
 * 通过 wx.login 接口获得临时登录凭证 code 后传到开发者服务器调用此接口完成登录流程。
 *
 * @see https://developers.weixin.qq.com/miniprogram/dev/OpenApiDoc/user-login/code2Session.html
 */
class Code2Session
{
    /**
     * 微信 API 基础地址
     */
    const BASE_URL = 'https://api.weixin.qq.com';

    /**
     * code2Session 接口路径
     */
    const CODE2SESSION_PATH = '/sns/jscode2session';

    /**
     * @var array 配置信息
     */
    protected array $config;

    /**
     * @var Client HTTP 客户端
     */
    protected Client $client;

    /**
     * @param array $config 配置数组
     * @param string $config['app_id'] 小程序 appId
     * @param string $config['app_secret'] 小程序 appSecret
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->client = new Client([
            'base_uri' => self::BASE_URL,
            'timeout' => $config['timeout'] ?? 5.0,
            'verify' => $config['verify_ssl'] ?? true,
        ]);
    }

    /**
     * 登录凭证校验
     *
     * 通过前端 wx.login() 获取的 code 换取 openid 和 session_key。
     *
     * @param string $code 登录时获取的 code
     * @return array 返回包含以下字段的数组：
     *   - openid: string 用户唯一标识
     *   - session_key: string 会话密钥
     *   - unionid: string|null 用户在开放平台的唯一标识符（已绑定开放平台账号时返回）
     * @throws WeChatException
     */
    public function handle(string $code): array
    {
        $appId = $this->config['app_id'] ?? '';
        $appSecret = $this->config['app_secret'] ?? '';

        if (empty($appId)) {
            throw new WeChatException('app_id 配置不能为空');
        }

        if (empty($appSecret)) {
            throw new WeChatException('app_secret 配置不能为空');
        }

        if (empty($code)) {
            throw new WeChatException('code 不能为空');
        }

        try {
            $response = $this->client->get(self::CODE2SESSION_PATH, [
                'query' => [
                    'appid' => $appId,
                    'secret' => $appSecret,
                    'js_code' => $code,
                    'grant_type' => 'authorization_code',
                ],
            ]);

            $body = (string)$response->getBody();
            $result = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new WeChatException('微信接口返回数据解析失败: ' . $body);
            }

            // 检查错误码
            $errCode = $result['errcode'] ?? 0;
            if ($errCode !== 0) {
                $errMsg = $result['errmsg'] ?? '未知错误';
                throw new WeChatException(
                    "微信 code2Session 失败: [{$errCode}] {$errMsg}",
                    $errCode,
                    $errMsg
                );
            }

            return [
                'openid' => $result['openid'] ?? '',
                'session_key' => $result['session_key'] ?? '',
                'unionid' => $result['unionid'] ?? null,
            ];
        } catch (WeChatException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new WeChatException('请求微信接口异常: ' . $e->getMessage());
        }
    }
}
