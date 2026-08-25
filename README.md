# webman-wechat

webman 微信小程序 SDK 组件。

## 安装

```bash
composer require xuezhitech/webman-wechat
```

## 功能

- [x] 小程序登录凭证校验（auth.code2Session）
- [x] 小程序获取手机号（phone.getPhoneNumber）

## 使用方式

### 1. 实例化并传入配置

```php
use xuezhitech\wechat\WeChat;

$wechat = new WeChat([
    'app_id'     => 'your_app_id',       // 小程序 appId（必填）
    'app_secret' => 'your_app_secret',   // 小程序 appSecret（必填）
    'timeout'    => 5.0,                 // 请求超时时间，单位秒（可选，默认 5）
    'verify_ssl' => true,                // 是否验证 SSL 证书（可选，默认 true）
]);
```

### 2. 在 webman 中使用

推荐在 webman 的配置文件中定义微信配置，例如 `config/plugin/xuezhitech/wechat/wechat.php`：

```php
return [
    'app_id'     => env('WECHAT_APP_ID', ''),
    'app_secret' => env('WECHAT_APP_SECRET', ''),
];
```

然后在控制器中使用：

```php
<?php

namespace app\controller;

use support\Request;
use xuezhitech\wechat\WeChat;
use xuezhitech\wechat\Exception\WeChatException;

class WeChatController
{
    public function login(Request $request)
    {
        $code = $request->input('code');

        // 从配置中读取，也可以从数据库或外部传入
        $config = config('plugin.xuezhitech.wechat.wechat');

        $wechat = new WeChat($config);

        try {
            $result = $wechat->auth->handle($code);
            return json([
                'code' => 0,
                'data' => [
                    'openid'    => $result['openid'],
                    'session_key' => $result['session_key'],
                    'unionid'   => $result['unionid'],
                ],
            ]);
        } catch (WeChatException $e) {
            return json([
                'code'    => $e->getWechatErrCode(),
                'message' => $e->getWechatErrMsg(),
            ]);
        }
    }
}
```

### 3. auth.code2Session 说明

该接口用于小程序登录凭证校验，对应微信官方接口：

```
GET https://api.weixin.qq.com/sns/jscode2session?appid=APPID&secret=SECRET&js_code=JSCODE&grant_type=authorization_code
```

**调用方式：**

```php
$result = $wechat->auth->handle($code);
```

**返回结果：**

```php
[
    'openid'      => 'oXXXX',         // 用户唯一标识
    'session_key' => 'xxxxx',         // 会话密钥
    'unionid'     => 'uXXXX' | null,  // 开放平台唯一标识符（已绑定时返回）
]
```

**错误处理：**

失败时抛出 `WeChatException`，可通过以下方法获取错误信息：

```php
try {
    $result = $wechat->auth->handle($code);
} catch (WeChatException $e) {
    $e->getWechatErrCode(); // 微信错误码，如 40029
    $e->getWechatErrMsg();  // 微信错误信息，如 "code 无效"
    $e->getMessage();       // 完整错误描述
}
```

**常见错误码：**

| errcode | 说明 |
|---------|------|
| 0       | 请求成功 |
| -1      | 系统繁忙，请稍后再试 |
| 40029   | code 无效 |
| 45011   | 频率限制，每个用户每分钟 100 次 |
| 40226   | 高风险等级用户，小程序登录拦截 |

### 4. phone.getPhoneNumber 说明

该接口用于小程序获取手机号，对应微信官方接口：

```
POST https://api.weixin.qq.com/wxa/business/getuserphonenumber?access_token=ACCESS_TOKEN
```

**前置条件：**

前端需通过 `<button open-type="getPhoneNumber">` 组件触发用户授权，在回调事件中获取 `code`。

**调用方式：**

```php
$phoneInfo = $wechat->phone->handle($code);
```

**返回结果：**

```php
[
    'phoneNumber'     => 'xxxxxx',  // 用户绑定的手机号（国家码和手机号间无分隔符）
    'purePhoneNumber' => 'xxxxxx',  // 纯 11 位手机号码
    'countryCode'     => '86',      // 国际区号
    'watermark'       => [          // 数据水印
        'appid'     => 'xxxxxx',
        'timestamp' => 1637744274,
    ],
]
```

**错误处理：**

失败时抛出 `WeChatException`，可通过以下方法获取错误信息：

```php
try {
    $phoneInfo = $wechat->phone->handle($code);
} catch (WeChatException $e) {
    $e->getWechatErrCode(); // 微信错误码
    $e->getWechatErrMsg();  // 微信错误信息
    $e->getMessage();       // 完整错误描述
}
```

**常见错误码：**

| errcode | 说明 |
|---------|------|
| 0       | 请求成功 |
| 40029   | code 无效 |
| 40013   | 非法 AppID |
| 40001   | access_token 无效 |
| 40014   | access_token 过期 |

**在 webman 控制器中使用示例：**

```php
public function getPhoneNumber(Request $request)
{
    $code = $request->input('code');

    $config = config('plugin.xuezhitech.wechat.wechat');
    $wechat = new WeChat($config);

    try {
        $phoneInfo = $wechat->phone->handle($code);
        return json([
            'code' => 0,
            'data' => $phoneInfo,
        ]);
    } catch (WeChatException $e) {
        return json([
            'code'    => $e->getWechatErrCode(),
            'message' => $e->getWechatErrMsg(),
        ]);
    }
}
```

## License

Apache-2.0
