<?php

namespace xuezhitech\wechat\UnlimitedQRCode;

use GuzzleHttp\Client;
use xuezhitech\wechat\Constant\ApiUrl;
use xuezhitech\wechat\Exception\WeChatException;
use xuezhitech\wechat\Util\Helper;
use xuezhitech\wechat\WeChat;

/**
 * 小程序获取不限制数量的小程序码（getwxacodeunlimit）
 *
 * 获取小程序码，适用于需要的码数量极多的业务场景。通过该接口生成的小程序码，
 * 永久有效，数量暂无限制。用户扫该码进入小程序后，开发者需在对应页面获取详情
 * 场景值（scene 参数由 scene 字段拼入，如 scene=1000 或 scene=a%3D1%26b%3D2）。
 *
 * 注意：POST 参数需要转义（例如 scene 中不能使用 & 字符，需先进行 URL 编码）。
 *
 * @see https://developers.weixin.qq.com/miniprogram/dev/OpenApiDoc/qrcode-code/constant/getUnlimitedQRCode.html
 */
class getUnlimitedQRCode
{
    /**
     * 获取不限制数量的小程序码接口路径
     */
    const QR_CODE_PATH = '/wxa/getwxacodeunlimit';

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
     * 获取不限制数量的小程序码
     *
     * 调用 getwxacodeunlimit 接口生成小程序码，成功返回小程序码图片的二进制
     * 内容（Buffer），失败抛出异常。
     *
     * @param string $scene 最大 32 个可见字符，只支持数字、大小写英文以及
     *   !#$&'()*+,/:;=?@-._~ 等字符。包含允许范围之外的字符时需先自行转义，
     *   例如 & 需写成 %26（如 hid=1%26type=2），不要整串 urlencode。
     * @param array $options 可选参数，支持的键：
     *   - page: string 默认是主页，必须是已经发布的小程序存在的页面（否则报错），
     *     例如 pages/index/index，根路径前不要填加 /，不能携带参数
     *   - check_path: bool 默认 true，检查 page 是否存在；为 true 时 page 必须是
     *     已发布的小程序存在的页面，为 false 时可与 env_version 配合使用
     *   - env_version: string 要打开的小程序版本，release（正式版）、trial（体验版）、
     *     develop（开发版），默认 release
     *   - width: int 生成的二维码的宽度，单位 px，范围 280 ~ 1280，默认 430
     *   - auto_color: bool 自动配置线条颜色，如果颜色依然是黑色，则推荐使用
     *     line_color 配置，默认 false
     *   - line_color: array 线条颜色，格式 ['r' => 0, 'g' => 0, 'b' => 0]，
     *     auto_color 为 false 时生效
     *   - is_hyaline: bool 是否需要透明底色，为 true 时生成透明底色的小程序码，
     *     默认 false
     * @return string 小程序码图片的二进制内容（Buffer）
     * @throws WeChatException
     */
    public function handle(string $scene, array $options = []): string
    {
        $accessToken = Helper::getAccessToken($this->wechat->getConfig(), $this->client);

        Helper::checkNotEmpty([
            'scene' => $scene,
        ]);

        if (mb_strlen($scene) > 32) {
            throw new WeChatException('scene 不能超过 32 个可见字符');
        }

        try {
            $response = $this->client->post(self::QR_CODE_PATH, [
                'query' => [
                    'access_token' => $accessToken,
                ],
                'json' => array_merge([
                    'scene' => $scene,
                ], $options),
            ]);

            $body = (string)$response->getBody();

            // 成功时返回图片二进制内容，失败时返回 JSON 错误信息
            $contentType = $response->getHeaderLine('Content-Type');
            if (stripos($contentType, 'application/json') !== false) {
                $result = json_decode($body, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new WeChatException('微信接口返回数据解析失败: ' . $body);
                }

                $errCode = $result['errcode'] ?? 0;
                if ($errCode !== 0) {
                    $errMsg = $result['errmsg'] ?? '未知错误';
                    throw new WeChatException(
                        "微信 getwxacodeunlimit 失败: [{$errCode}] {$errMsg}",
                        $errCode,
                        $errMsg
                    );
                }
            }

            if (empty($body)) {
                throw new WeChatException('微信返回的小程序码内容为空');
            }

            return $body;
        } catch (WeChatException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new WeChatException('请求微信接口异常: ' . $e->getMessage());
        }
    }
}
