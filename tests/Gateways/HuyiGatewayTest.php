<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-03-20
 * Time: 下午 5:15
 */

namespace Overtrue\EasySms\Tests\Gateways;

use Overtrue\EasySms\Gateways\HuyiGateway;
use Overtrue\EasySms\Tests\TestCase;

class HuyiGatewayTest extends TestCase
{
    public function testSend()
    {
        $gateway = \Mockery::mock(HuyiGateway::class.'[post]', [[
            'APIID' => 'mock-api-id',
            'APIKEY' => 'mock-api-key',
        ]])->shouldAllowMockingProtectedMethods();

        $params= [
            'account' => 'mock-api-id',
            'mobile' => strval(18188888888),
            'content' => 'This is a huyi test message.',
            'time' => time(),
            'format' => 'json'
        ];
        $params['sign']=$this->generateSign($params);
        $gateway->expects()->post('http://106.ihuyi.com/webservice/sms.php?method=Submit', $params)
            ->andReturn('mock-result')->once();
        $this->assertSame('mock-result', $gateway->send(18188888888,'This is a huyi test message.'));
    }


    protected function generateSign($params)
    {
        return md5($params['account'].'mock-api-key'.$params['mobile'].$params['content'].$params['time']);
    }
}