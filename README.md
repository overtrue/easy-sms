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


# 环境需求

- PHP >= 5.6

# 安装

```shell
$ composer require "overtrue/easy-sms"
```

# 使用

```php
use Overtrue\EasySms\EasySms;

$config = [
    'default' => 'error-log',
    'shuffle_gateways' => true, 
    'enabled_gateways' => ['yunpian', 'alidayu'],
    'gateways' => [
        'error-log' => [
            'file' => '/tmp/easy-sms.log',
        ],
        'yunpian' => [
            'api_key' => '824f0ff2f71cab52936a13ede3xxxxx',
        ],
    ],
];

$easySms = new EasySms($config);
$easySms->send(13188888888, 'hello world!');
```

# 定义短信

你可以根本发送场景的不同，定义不同的短信类，从而实现一处定义多处调用，你可以继承 `Overtrue\EasySms\Message` 来定义短信模型：

```php
<?php

use Overtrue\EasySms\Message;

class OrderPaidMessage extends Messeage
{
    protected $order;
    protected $gateways = ['alidayu', 'yunpian']; // 定义本短信的适用平台，覆盖全局配置中的 `enabled_gateways`

    public function __construct($order)
    {
        $this->order = $order;
    }
        
    // 定义直接使用内容发送平台的内容
    public function getContent()
    {
        return sprintf('您的订单:%s, 已经完成付款', $this->order->no);    
    }
    
    // 定义使用模板发送方式平台所需要的模板 ID
    public function getTemplate()
    {
        return 'SMS_003'; 
    }
        
    // 模板参数
    public function getData()
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

# 平台支持

- [云片](https://github.com/overtrue/easy-sms/wiki/GateWays---Yunpian)
- [Submail](https://github.com/overtrue/easy-sms/wiki/GateWays---Submail)
- [螺丝帽](https://github.com/overtrue/easy-sms/wiki/GateWays---Luosimao)
- [阿里大鱼](https://github.com/overtrue/easy-sms/wiki/GateWays---AliDayu)
- [容联云通讯](https://github.com/overtrue/easy-sms/wiki/GateWays---Yuntongxun)
- [互亿无线](https://github.com/overtrue/easy-sms/wiki/GateWays---Huyi)
- SendCloud

# License

MIT
