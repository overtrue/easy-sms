<h1 align="center">Easy SMS</h1>

<p align="center">:calling: 一款满足你的多种发送需求的短信发送组件</p>

<p align="center">
<a href="https://travis-ci.org/overtrue/easy-sms"><img src="https://travis-ci.org/overtrue/easy-sms.svg?branch=master" alt="Build Status"></a>
<a href="https://packagist.org/packages/overtrue/easy-sms"><img src="https://poser.pugx.org/overtrue/easy-sms/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/overtrue/easy-sms"><img src="https://poser.pugx.org/overtrue/easy-sms/v/unstable.svg" alt="Latest Unstable Version"></a>
<a href="https://scrutinizer-ci.com/g/overtrue/easy-sms/?branch=master"><img src="https://scrutinizer-ci.com/g/overtrue/easy-sms/badges/quality-score.png?b=master" alt="Scrutinizer Code Quality"></a>
<a href="https://scrutinizer-ci.com/g/overtrue/easy-sms/?branch=master"><img src="https://scrutinizer-ci.com/g/overtrue/easy-sms/badges/coverage.png?b=master" alt="Code Coverage"></a>
<a href="https://packagist.org/packages/overtrue/easy-sms"><img src="https://poser.pugx.org/overtrue/easy-sms/downloads" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/overtrue/easy-sms"><img src="https://poser.pugx.org/overtrue/easy-sms/license" alt="License"></a>
</p>


## 特点

1. 支持目前市面多家服务商
1. 一套写法兼容所有平台
1. 简单配置即可灵活增减服务商
1. 内置多种服务商轮询策略、支持自定义轮询策略
1. 统一的返回值格式，便于日志与监控
1. 自动轮询选择可用的服务商
1. 更多等你去发现与改进...

## 平台支持

- [阿里云](https://www.aliyun.com/)
- [云片](https://www.yunpian.com)
- [Submail](https://www.mysubmail.com)
- [螺丝帽](https://luosimao.com/)
- [容联云通讯](http://www.yuntongxun.com)
- [互亿无线](http://www.ihuyi.com)
- [聚合数据](https://www.juhe.cn)
- [SendCloud](http://www.sendcloud.net/)
- [百度云](https://cloud.baidu.com/)
- [华信短信平台](http://www.ipyy.com/)
- [253云通讯（创蓝）](https://www.253.com/)
- [融云](http://www.rongcloud.cn)
- [天毅无线](http://www.85hu.com/)
- [腾讯云 SMS](https://cloud.tencent.com/product/sms)
- [阿凡达数据](http://www.avatardata.cn/)
- [华为云](https://www.huaweicloud.com/product/msgsms.html)

## 环境需求

- PHP >= 5.6

## 安装

```shell
$ composer require "overtrue/easy-sms"
```

## 使用

```php
use Overtrue\EasySms\EasySms;

$config = [
    // HTTP 请求的超时时间（秒）
    'timeout' => 5.0,

    // 默认发送配置
    'default' => [
        // 网关调用策略，默认：顺序调用
        'strategy' => \Overtrue\EasySms\Strategies\OrderStrategy::class,

        // 默认可用的发送网关
        'gateways' => [
            'yunpian', 'aliyun',
        ],
    ],
    // 可用的网关配置
    'gateways' => [
        'errorlog' => [
            'file' => '/tmp/easy-sms.log',
        ],
        'yunpian' => [
            'api_key' => '824f0ff2f71cab52936axxxxxxxxxx',
        ],
        'aliyun' => [
            'access_key_id' => '',
            'access_key_secret' => '',
            'sign_name' => '',
        ],
        //...
    ],
];

$easySms = new EasySms($config);

$easySms->send(13188888888, [
    'content'  => '您的验证码为: 6379',
    'template' => 'SMS_001',
    'data' => [
        'code' => 6379
    ],
]);
```

## 短信内容

由于使用多网关发送，所以一条短信要支持多平台发送，每家的发送方式不一样，但是我们抽象定义了以下公用属性：

- `content` 文字内容，使用在像云片类似的以文字内容发送的平台
- `template` 模板 ID，使用在以模板ID来发送短信的平台
- `data`  模板变量，使用在以模板ID来发送短信的平台

所以，在使用过程中你可以根据所要使用的平台定义发送的内容。

```php
$easySms->send(13188888888, [
    'content'  => '您的验证码为: 6379',
    'template' => 'SMS_001',
    'data' => [
        'code' => 6379
    ],
]);
```

你也可以使用闭包来返回对应的值：

```php
$easySms->send(13188888888, [
    'content'  => function($gateway){
        return '您的验证码为: 6379';
    },
    'template' => function($gateway){
        return 'SMS_001';
    },
    'data' => function($gateway){
        return [
            'code' => 6379
        ];
    },
]);
```

你可以根据 `$gateway` 参数类型来判断返回值，例如：

```php
$easySms->send(13188888888, [
    'content'  => function($gateway){
        if ($gateway->getName() == 'yunpian') {
            return '云片专用验证码：1235';
        }
        return '您的验证码为: 6379';
    },
    'template' => function($gateway){
        if ($gateway->getName() == 'aliyun') {
            return 'TP2818';
        }
        return 'SMS_001';
    },
    'data' => function($gateway){
        return [
            'code' => 6379
        ];
    },
]);
```

## 发送网关

默认使用 `default` 中的设置来发送，如果某一条短信你想要覆盖默认的设置。在 `send` 方法中使用第三个参数即可：

```php
$easySms->send(13188888888, [
    'content'  => '您的验证码为: 6379',
    'template' => 'SMS_001',
    'data' => [
        'code' => 6379
    ],
 ], ['yunpian', 'juhe']); // 这里的网关配置将会覆盖全局默认值
```

## 返回值

由于使用多网关发送，所以返回值为一个数组，结构如下：
```php
[
    'yunpian' => [
        'gateway' => 'yunpian',
        'status' => 'success',
        'result' => [...] // 平台返回值
    ],
    'juhe' => [
        'gateway' => 'juhe',
        'status' => 'failure',
        'exception' => \Overtrue\EasySms\Exceptions\GatewayErrorException 对象
    ],
    //...
]
```

如果所选网关列表均发送失败时，将会抛出 `Overtrue\EasySms\Exceptions\NoGatewayAvailableException` 异常，你可以使用 `$e->results` 获取发送结果。

你也可以使用 `$e` 提供的更多便捷方法：

```php
$e->getResults();               // 返回所有 API 的结果，结构同上
$e->getExceptions();            // 返回所有调用异常列表
$e->getException($gateway);     // 返回指定网关名称的异常对象
$e->getLastException();         // 获取最后一个失败的异常对象 
```

## 自定义网关

本拓展已经支持用户自定义网关，你可以很方便的配置即可当成与其它拓展一样的使用：

```php
$config = [
    ...
    'default' => [
        'gateways' => [
            'mygateway', // 配置你的网站到可用的网关列表
        ],
    ],
    'gateways' => [
        'mygateway' => [...], // 你网关所需要的参数，如果没有可以不配置
    ],
];

$easySms = new EasySms($config);

// 注册
$easySms->extend('mygateway', function($gatewayConfig){
    // $gatewayConfig 来自配置文件里的 `gateways.mygateway`
    return new MyGateway($gatewayConfig);
});

$easySms->send(13188888888, [
    'content'  => '您的验证码为: 6379',
    'template' => 'SMS_001',
    'data' => [
        'code' => 6379
    ],
]);
```

## 国际短信

国际短信与国内短信的区别是号码前面需要加国际码，但是由于各平台对国际号码的写法不一致，所以在发送国际短信的时候有一点区别：

```php
use Overtrue\EasySms\PhoneNumber;

// 发送到国际码为 31 的国际号码
$number = new PhoneNumber(13188888888, 31);

$easySms->send($number, [
    'content'  => '您的验证码为: 6379',
    'template' => 'SMS_001',
    'data' => [
        'code' => 6379
    ],
]);
```

## 定义短信

你可以根据发送场景的不同，定义不同的短信类，从而实现一处定义多处调用，你可以继承 `Overtrue\EasySms\Message` 来定义短信模型：

```php
<?php

use Overtrue\EasySms\Message;
use Overtrue\EasySms\Contracts\GatewayInterface;
use Overtrue\EasySms\Strategies\OrderStrategy;

class OrderPaidMessage extends Message
{
    protected $order;
    protected $strategy = OrderStrategy::class;           // 定义本短信的网关使用策略，覆盖全局配置中的 `default.strategy`
    protected $gateways = ['alidayu', 'yunpian', 'juhe']; // 定义本短信的适用平台，覆盖全局配置中的 `default.gateways`

    public function __construct($order)
    {
        $this->order = $order;
    }

    // 定义直接使用内容发送平台的内容
    public function getContent(GatewayInterface $gateway = null)
    {
        return sprintf('您的订单:%s, 已经完成付款', $this->order->no);    
    }

    // 定义使用模板发送方式平台所需要的模板 ID
    public function getTemplate(GatewayInterface $gateway = null)
    {
        return 'SMS_003';
    }

    // 模板参数
    public function getData(GatewayInterface $gateway = null)
    {
        return [
            'order_no' => $this->order->no    
        ];    
    }
}
```

> 更多自定义方式请参考：[`Overtrue\EasySms\Message`](Overtrue\EasySms\Message;)

发送自定义短信：

```php
$order = ...;
$message = new OrderPaidMessage($order);

$easySms->send(13188888888, $message);
```

## 各平台配置说明

### [阿里云](https://www.aliyun.com/)

短信内容使用 `template` + `data`

```php
    'aliyun' => [
        'access_key_id' => '',
        'access_key_secret' => '',
        'sign_name' => '',
    ],
```

### [阿里云Rest](https://www.aliyun.com/)

短信内容使用 `template` + `data`

```php
    'aliyunrest' => [
        'app_key' => '',
        'app_secret_key' => '',
        'sign_name' => '',
    ],
```

### [云片](https://www.yunpian.com)

短信内容使用 `content`

```php
    'yunpian' => [
        'api_key' => '',
        'signature' => '【默认签名】', // 内容中无签名时使用
    ],
```

### [Submail](https://www.mysubmail.com)

短信内容使用 `data`

```php
    'submail' => [
        'app_id' => '',
        'app_key' => '',
        'project' => '', // 默认 project，可在发送时 data 中指定
    ],
```

### [螺丝帽](https://luosimao.com/)

短信内容使用 `content`

```php
    'luosimao' => [
        'api_key' => '',
    ],
```

### [容联云通讯](http://www.yuntongxun.com)

短信内容使用 `template` + `data`

```php
    'yuntongxun' => [
        'app_id' => '',
        'account_sid' => '',
        'account_token' => '',
        'is_sub_account' => false,
    ],
```

### [互亿无线](http://www.ihuyi.com)

短信内容使用 `content`

```php
    'huyi' => [
        'api_id' => '',
        'api_key' => '',
        'signature' => '',
    ],
```

### [聚合数据](https://www.juhe.cn)

短信内容使用 `template` + `data`

```php
    'juhe' => [
        'app_key' => '',
    ],
```

### [SendCloud](http://www.sendcloud.net/)

短信内容使用 `template` + `data`

```php
    'sendcloud' => [
        'sms_user' => '',
        'sms_key' => '',
        'timestamp' => false, // 是否启用时间戳
    ],
```
### [百度云](https://cloud.baidu.com/)

短信内容使用 `template` + `data`

```php
    'baidu' => [
        'ak' => '',
        'sk' => '',
        'invoke_id' => '',
        'domain' => '',
    ],
```

### [华信短信平台](http://www.ipyy.com/)

短信内容使用 `content`

```php
    'huaxin' => [
        'user_id'  => '',
        'password' => '',
        'account'  => '',
        'ip'       => '',
        'ext_no'   => '',
    ],
```

### [253云通讯（创蓝）](https://www.253.com/)

短信内容使用 `content`

```php
    'chuanglan' => [
        'account' => '',
        'password' => '',

        // \Overtrue\EasySms\Gateways\ChuanglanGateway::CHANNEL_VALIDATE_CODE  => 验证码通道（默认）
        // \Overtrue\EasySms\Gateways\ChuanglanGateway::CHANNEL_PROMOTION_CODE => 会员营销通道
        'channel'  => \Overtrue\EasySms\Gateways\ChuanglanGateway::CHANNEL_VALIDATE_CODE, 

        // 会员营销通道 特定参数。创蓝规定：api提交营销短信的时候，需要自己加短信的签名及退订信息
        'sign' => '【通讯云】',
        'unsubscribe' => '回TD退订', 
    ],
```

### [融云](http://www.rongcloud.cn)

短信分为两大类，验证类和通知类短信。 发送验证类短信使用 `template` + `data`

```php
    'rongcloud' => [
        'app_key' => '',
        'app_secret' => '',
    ]
```

### [天毅无线](http://www.85hu.com/)

短信内容使用 `content`

```php
    'tianyiwuxian' => [
        'username' => '', //用户名
        'password' => '', //密码
        'gwid' => '', //网关ID
    ]
```

### [twilio](https://www.twilio.com)

短信使用 `content`  
发送对象需要 使用`+`添加区号

```php
    'twilio' => [
        'account_sid' => '', // sid
        'from' => '', // 发送的号码 可以在控制台购买
        'token' => '', // apitoken
    ],
```

### [腾讯云 SMS](https://cloud.tencent.com/product/sms)

短信内容使用 `content`。

```php
    'qcloud' => [
        'sdk_app_id' => '', // SDK APP ID
        'app_key' => '', // APP KEY
        'sign_name' => '', // 短信签名，如果使用默认签名，该字段可缺省（对应官方文档中的sign）
    ],
```

### [阿凡达数据](http://www.avatardata.cn/)

短信内容使用 `template` + `data`

```php
    'avatardata' => [
        'app_key' => '', // APP KEY
    ],
```

### [华为云 SMS](https://www.huaweicloud.com/product/msgsms.html)

短信内容使用 `template` + `data`

```php
    'huawei' => [
        'endpoint' => '', // APP接入地址
        'app_key' => '', // APP KEY
        'app_secret' => '', // APP SECRET
        'from' => [
            'default' => '1069012345', // 默认使用签名通道号
            'custom' => 'csms12345', // 其他签名通道号 可以在 data 中定义 from 来指定
            'abc' => 'csms67890', // 其他签名通道号
            ...
        ],
        'callback' => '' // 短信状态回调地址
    ],
```

使用默认签名通道 `default`

```php
$easySms->send(13188888888, [
    'template' => 'SMS_001',
    'data' => [
        6379
    ],
]);
```

使用指定签名通道

```php
$easySms->send(13188888888, [
    'template' => 'SMS_001',
    'data' => [
        6379,
        'from' => 'custom' // 对应 config 中的 from 数组中 custom
    ],
]);
```

## PHP 扩展包开发

> 想知道如何从零开始构建 PHP 扩展包？
>
> 请关注我的实战课程，我会在此课程中分享一些扩展开发经验 —— [《PHP 扩展包实战教程 - 从入门到发布》](https://learnku.com/courses/creating-package)

## License

MIT
