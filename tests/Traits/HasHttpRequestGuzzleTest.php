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
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Overtrue\EasySms\Tests\TestCase;
use Overtrue\EasySms\Traits\HasHttpRequest;

/**
 * Makes sure the trait plays well with a real Guzzle client, so changes in
 * Guzzle (request method casing, option types, header value types) are caught
 * instead of being hidden behind a mocked request() call.
 */
class HasHttpRequestGuzzleTest extends TestCase
{
    public function test_get_request()
    {
        $object = new GuzzleBackedDummyClassForHasHttpRequestTrait;

        $this->assertSame(['code' => 0, 'msg' => 'ok'], $object->sendGet('sub', ['foo' => 'bar'], ['X-Mock' => 'yes']));

        $request = $object->history[0]['request'];
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('https://example.com/sub?foo=bar', (string) $request->getUri());
        $this->assertSame('yes', $request->getHeaderLine('X-Mock'));
    }

    public function test_post_request()
    {
        $object = new GuzzleBackedDummyClassForHasHttpRequestTrait;

        $this->assertSame(['code' => 0, 'msg' => 'ok'], $object->sendPost('sub', ['foo' => 'bar'], ['X-Mock' => 'yes']));

        $request = $object->history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('yes', $request->getHeaderLine('X-Mock'));
        $this->assertSame('foo=bar', (string) $request->getBody());
    }

    public function test_post_json_request()
    {
        $object = new GuzzleBackedDummyClassForHasHttpRequestTrait;

        $this->assertSame(['code' => 0, 'msg' => 'ok'], $object->sendPostJson('sub', ['foo' => 'bar'], ['X-Mock' => 'yes']));

        $request = $object->history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('yes', $request->getHeaderLine('X-Mock'));
        $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
        $this->assertSame('{"foo":"bar"}', (string) $request->getBody());
    }
}

class GuzzleBackedDummyClassForHasHttpRequestTrait
{
    use HasHttpRequest;

    /**
     * @var array
     */
    public $history = [];

    public function sendGet($endpoint, $query = [], $headers = [])
    {
        return $this->get($endpoint, $query, $headers);
    }

    public function sendPost($endpoint, $params = [], $headers = [])
    {
        return $this->post($endpoint, $params, $headers);
    }

    public function sendPostJson($endpoint, $params = [], $headers = [])
    {
        return $this->postJson($endpoint, $params, $headers);
    }

    public function getBaseUri()
    {
        return 'https://example.com';
    }

    public function getHttpClient(array $options = [])
    {
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], '{"code":0,"msg":"ok"}'),
        ]));
        $stack->push(Middleware::history($this->history));

        $options['handler'] = $stack;
        $options['http_errors'] = false;

        return new Client($options);
    }
}
