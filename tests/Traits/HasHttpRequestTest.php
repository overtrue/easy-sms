<?php

/*
 * This file is part of the overtrue/easy-sms.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\EasySms\Tests\Traits;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Overtrue\EasySms\Tests\TestCase;
use Overtrue\EasySms\Traits\HasHttpRequest;
use Psr\Http\Message\ResponseInterface;

class HasHttpRequestTest extends TestCase
{
    public function testRequest()
    {
        $object = \Mockery::mock(DummyClassForHasHttpRequestTrait::class)
                ->shouldAllowMockingProtectedMethods();
        $mockBaseOptions = ['base_uri' => 'https://mock-base-options'];
        $mockResponse = \Mockery::mock(ResponseInterface::class);
        $mockHttpClient = \Mockery::mock(Client::class);
        $object->expects()->getHttpClient($mockBaseOptions)
                ->andReturn($mockHttpClient)
                ->once();
        $object->expects()->getBaseOptions()->andReturn($mockBaseOptions);
        $object->expects()->unwrapResponse($mockResponse)->andReturn('unwrapped-api-result');

        $options = ['form_params' => ['foo' => 'bar']];
        $mockHttpClient->allows()->get('mock-endpoint', $options)->andReturn($mockResponse)->once();
        $object->allows()->request(anyArgs())->passthru();

        $this->assertSame('unwrapped-api-result', $object->request('get', 'mock-endpoint', $options));
    }

    public function testGet()
    {
        $object = \Mockery::mock(DummyClassForHasHttpRequestTrait::class)
                ->shouldAllowMockingProtectedMethods();
        $object->expects()->request('get', 'mock-endpoint', [
            'headers' => ['Content-Type' => 'Mock-Content-Type'],
            'query' => ['foo' => 'bar'],
        ])->andReturns('mock-result')->once();
        $object->allows()->get(anyArgs())->passthru();

        $response = $object->get('mock-endpoint', ['foo' => 'bar'], ['Content-Type' => 'Mock-Content-Type']);

        $this->assertSame('mock-result', $response);
    }

    public function testPost()
    {
        $object = \Mockery::mock(DummyClassForHasHttpRequestTrait::class)
            ->shouldAllowMockingProtectedMethods();
        $object->expects()->request('post', 'mock-endpoint', [
            'headers' => ['Content-Type' => 'Mock-Content-Type'],
            'form_params' => ['foo' => 'bar'],
        ])->andReturns('mock-result')->once();
        $object->allows()->post(anyArgs())->passthru();

        $response = $object->post('mock-endpoint', ['foo' => 'bar'], ['Content-Type' => 'Mock-Content-Type']);

        $this->assertSame('mock-result', $response);
    }

    public function testGetBaseOptions()
    {
        $object = \Mockery::mock(DummyClassForHasHttpRequestTrait::class)
                ->makePartial()
                ->shouldAllowMockingProtectedMethods();
        $object->allows()->getBaseOptions(anyArgs())->passthru();

        $this->assertSame('http://mock-uri', $object->getBaseOptions()['base_uri']);
        $this->assertSame(5.0, $object->getBaseOptions()['timeout']);

        // timeout overwrite
        $object = \Mockery::mock(DummyTimeoutClassForHasHttpRequestTrait::class)
                ->makePartial()
                ->shouldAllowMockingProtectedMethods();
        $object->allows()->getBaseOptions(anyArgs())->passthru();

        $this->assertSame('http://mock-uri', $object->getBaseOptions()['base_uri']);
        $this->assertSame(30.0, $object->getBaseOptions()['timeout']);
    }

    public function testUnwrapResponseWithJsonResponse()
    {
        $object = \Mockery::mock(DummyClassForHasHttpRequestTrait::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $object->allows()->unwrapResponse(anyArgs())->passthru();

        $body = ['foo' => 'bar'];
        $response = new Response(200, ['content-type' => 'application/json'], json_encode($body));

        $this->assertSame($body, $object->unwrapResponse($response));
    }

    public function testUnwrapResponseWithXMLResponse()
    {
        $object = \Mockery::mock(DummyClassForHasHttpRequestTrait::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $object->allows()->unwrapResponse(anyArgs())->passthru();

        $body = '<xml>
                    <foo>hello</foo>
                    <bar>world</bar>
                </xml>';
        $response = new Response(200, ['content-type' => 'application/xml'], $body);

        $this->assertSame(['foo' => 'hello', 'bar' => 'world'], $object->unwrapResponse($response));
    }

    public function testUnwrapResponseWithUnsupportedResponse()
    {
        $object = \Mockery::mock(DummyClassForHasHttpRequestTrait::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $object->allows()->unwrapResponse(anyArgs())->passthru();

        $body = 'something here.';
        $response = new Response(200, ['content-type' => 'text/plain'], $body);

        $this->assertSame('something here.', $object->unwrapResponse($response));
    }
}

class DummyClassForHasHttpRequestTrait
{
    use HasHttpRequest;

    public function getBaseUri()
    {
        return 'http://mock-uri';
    }
}

class DummyTimeoutClassForHasHttpRequestTrait
{
    use HasHttpRequest;

    public function getBaseUri()
    {
        return 'http://mock-uri';
    }

    public function getTimeout()
    {
        return 30.0;
    }
}
