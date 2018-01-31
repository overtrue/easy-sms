<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-01-31
 * Time: 18:47
 */
require __DIR__ . '/vendor/autoload.php';

use Overtrue\EasySms\EasySms;

$config = [
    // HTTP 请求的超时时间（秒）
    'timeout'  => 5.0,

    // 默认发送配置
    'default'  => [
        // 网关调用策略，默认：顺序调用
        'strategy' => Overtrue\EasySms\Strategies\OrderStrategy::class,

        // 默认可用的发送网关
        'gateways' => [
            'avatardata'
        ],
    ],
    // 可用的网关配置
    'gateways' => [
        'errorlog'   => [
            'file' => 'easy-sms.log',
        ],
        'avatardata' => [
            'app_key' => 'f01afaedc02843a88033fda8a4bd24f6',
        ],
    ],
];

$easySms = new EasySms($config);

try {
    $easySms->send(18001390000, [
        'content'  => '感谢您注册{1}，您的验证码是{2}。',
        'template' => '99a0f574f8f0442483e6************',
        'data'     => '秋林拾叶,3160',                      //多个参数使用英文逗号分隔
    ]);
} catch (Exception $exception) {
    echo $exception->getMessage();
}
