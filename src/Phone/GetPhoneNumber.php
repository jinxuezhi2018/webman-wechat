<?php

namespace xuezhitech\wechat\Phone;

use GuzzleHttp\Client;
use xuezhitech\wechat\Constant\ApiUrl;
use xuezhitech\wechat\Exception\WeChatException;
use xuezhitech\wechat\Util\Helper;
use xuezhitech\wechat\WeChat;

/**
 * 小程序获取手机号（getPhoneNumber）
 *
 * 前端通过 <button open-type="getPhoneNumber"> 组件触发用户授权，
 * 回调事件中会携带 code，将此 code 传到开发者服务器调用此接口获取手机号。
 *
 * @see https://developers.weixin.qq.com/miniprogram/dev/OpenApiDoc/user-info/phone-number/getPhoneNumber.html
 */
class GetPhoneNumber
{
    /**
     * 获取手机号接口路径
     */
    const PHONE_PATH = '/wxa/business/getuserphonenumber';

    /**
     * @var WeChat SDK 主实例
     */
    protected WeChat $wechat;

    /**
     * @var Client HTTP 客户端
     */
    protected Client $client;

    /**
     * @param WeChat $wechat SDK 主实例
     */
    public function __construct(WeChat $wechat)
    {
        $this->wechat = $wechat;
        $config = $wechat->getConfig();
        $this->client = new Client([
            'base_uri' => ApiUrl::BASE_URL,
            'timeout' => $config['timeout'] ?? 5.0,
            'verify' => $config['verify_ssl'] ?? true,
        ]);
    }

    /**
     * 获取手机号
     *
     * 通过前端 button 组件 open-type="getPhoneNumber" 回调事件中获取的 code 换取手机号。
     *
     * @param string $code 授权回调事件中获取的 code
     * @return array 返回包含以下字段的数组：
     *   - phone_info: array 用户手机号信息，包含：
     *     - phoneNumber: string 用户绑定的手机号（国家码和手机号间无分隔符）
     *     - purePhoneNumber: string 纯 11 位手机号码
     *     - countryCode: string 国际区号
     *     - watermark: array 数据水印，包含 appid 和 timestamp
     * @throws WeChatException
     */
    public function handle(string $code): array
    {
        $accessToken = Helper::getAccessToken($this->wechat->getConfig(), $this->client);

        Helper::checkNotEmpty([
            'code' => $code,
        ]);

        try {
            $response = $this->client->post(self::PHONE_PATH, [
                'query' => [
                    'access_token' => $accessToken,
                ],
                'json' => [
                    'code' => $code,
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
                    "微信 getPhoneNumber 失败: [{$errCode}] {$errMsg}",
                    $errCode,
                    $errMsg
                );
            }

            return $result['phone_info'] ?? [];
        } catch (WeChatException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new WeChatException('请求微信接口异常: ' . $e->getMessage());
        }
    }
}
