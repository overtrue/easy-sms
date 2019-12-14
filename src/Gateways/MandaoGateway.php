<?php

/*
 * This file is part of the overtrue/easy-sms.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\EasySms\Gateways;

use Overtrue\EasySms\Contracts\MessageInterface;
use Overtrue\EasySms\Contracts\PhoneNumberInterface;
use Overtrue\EasySms\Exceptions\GatewayErrorException;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Traits\HasHttpRequest;


class MandaoGateway extends Gateway
{
    use HasHttpRequest;

    const ENDPOINT_URL = 'http://sdk.entinfo.cn:8061/webservice.asmx/mdsmssend';

    /**
     * @param \Overtrue\EasySms\Contracts\PhoneNumberInterface $to
     * @param \Overtrue\EasySms\Contracts\MessageInterface     $message
     * @param \Overtrue\EasySms\Support\Config                 $config
     *
     * @return \Psr\Http\Message\ResponseInterface|array|string
     *
     * @throws \Overtrue\EasySms\Exceptions\GatewayErrorException ;
     */
    public function send(PhoneNumberInterface $to, MessageInterface $message, Config $config)
    {
        $params = [
            'sn' => $config->get('sn'),
            'mobile' => $to->getNumber(),
            'content' => $message->getContent($this),
            'ext' => $config->get('ext'),
            'stime' => $message->getData($this)['stime'] ?: '',
            'rrid' => $message->getData($this)['rrid'] ?: '',
            'msgfmt' => $message->getData($this)['msgfmt'] ?: '',
        ];

        $params['pwd'] = $this->generateSign();

        $result = $this->post(self::ENDPOINT_URL, $params);

        if ($result[0] < 0) {
            throw new GatewayErrorException($this->getError($result[0]), $result[0], $result);
        }

        return $result;
    }


    /**
     * Generate Sign.
     *
     * @return string
     */
    protected function generateSign()
    {
        return strtoupper(md5($this->config->get('sn') . $this->config->get('password')));
    }

    /**
     * 错误对照
     * @param $code
     * @return mixed|string
     */
    public function getError($code)
    {
        $error = [
            '-2' => '帐号/密码不正确',
            '-4' => '余额不足支持本次发送',
            '-5' => '数据格式错误',
            '-6' => '参数有误',
            '-7' => '权限受限',
            '-8' => '流量控制错误',
            '-9' => '扩展码权限错误',
            '-10' => '内容长度长',
            '-11' => '内部数据库错误',
            '-12' => '序列号状态错误',
            '-14' => '服务器写文件失败',
            '-17' => '没有权限',
            '-19' => '禁止同时使用多个接口地址',
            '-20' => '相同手机号，相同内容重复提交',
            '-21' => 'Ip鉴权失败',
            '-22' => 'Ip鉴权失败22',
            '-23' => '缓存无此序列号信息',
            '-601' => '序列号为空，参数错误',
            '-602' => '序列号格式错误，参数错误',
            '-603' => '密码为空，参数错误',
            '-604' => '手机号码为空，参数错误',
            '-605' => '内容为空，参数错误',
            '-606' => 'ext长度大于9，参数错误',
            '-607' => '参数错误 扩展码非数字 ',
            '-608' => '参数错误 定时时间非日期格式',
            '-609' => 'rrid长度大于18,参数错误 ',
            '-610' => '参数错误 rrid非数字',
            '-611' => '参数错误 内容编码不符合规范',
            '-623' => '手机个数与内容个数不匹配',
            '-624' => '扩展个数与手机个数不匹配',
            '-625' => '定时时间个数与手机个数不匹配',
            '-626' => 'rrid个数与手机个数不匹配'
        ];
        if (isset($error[$code])) {
            return $error[$code];
        } else {
            return '未知错误';
        }
    }
}