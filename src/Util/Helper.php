<?php

namespace xuezhitech\wechat\Util;

use GuzzleHttp\Client;
use xuezhitech\wechat\Exception\WeChatException;

/**
 * 工具类
 *
 * 提供公共的工具方法，如参数校验、获取 access_token 等。
 */
class Helper
{
    /**
     * 校验参数是否为空
     *
     * 支持校验单个或多个参数，任一参数为空则抛出异常。
     *
     * @param array $params 待校验的参数数组，键为参数名，值为参数值
     * @throws WeChatException 参数为空时抛出异常
     */
    public static function checkNotEmpty(array $params): void
    {
        foreach ($params as $name => $value) {
            if (empty($value)) {
                throw new WeChatException("{$name} 不能为空");
            }
        }
    }

    /**
     * 获取 access_token
     *
     * 通过 app_id 和 app_secret 调用微信接口获取接口调用凭据 access_token。
     *
     * @param array $config 配置数组
     * @param string $config['app_id'] 小程序 appId
     * @param string $config['app_secret'] 小程序 appSecret
     * @param Client $client HTTP 客户端
     * @return string access_token
     * @throws WeChatException
     */
    public static function getAccessToken(array $config, Client $client): string
    {
        self::checkNotEmpty([
            'app_id' => $config['app_id'] ?? '',
            'app_secret' => $config['app_secret'] ?? '',
        ]);

        try {
            $response = $client->get('/cgi-bin/token', [
                'query' => [
                    'grant_type' => 'client_credential',
                    'appid' => $config['app_id'],
                    'secret' => $config['app_secret'],
                ],
            ]);

            $body = (string)$response->getBody();
            $result = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new WeChatException('微信接口返回数据解析失败: ' . $body);
            }

            $errCode = $result['errcode'] ?? 0;
            if ($errCode !== 0) {
                $errMsg = $result['errmsg'] ?? '未知错误';
                throw new WeChatException(
                    "微信获取 access_token 失败: [{$errCode}] {$errMsg}",
                    $errCode,
                    $errMsg
                );
            }

            $accessToken = $result['access_token'] ?? '';
            if (empty($accessToken)) {
                throw new WeChatException('微信返回的 access_token 为空');
            }

            return $accessToken;
        } catch (WeChatException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new WeChatException('请求微信接口异常: ' . $e->getMessage());
        }
    }
}
