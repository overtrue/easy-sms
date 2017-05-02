<?php

/*
 * This file is part of the overtrue/easy-sms.
 * (c) overtrue <i@overtrue.me>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\EasySms;

use Closure;
use Overtrue\EasySms\Contracts\GatewayInterface;
use Overtrue\EasySms\Contracts\StrategyInterface;
use Overtrue\EasySms\Exceptions\InvalidArgumentException;
use Overtrue\EasySms\Strategies\OrderStrategy;
use Overtrue\EasySms\Support\Config;
use RuntimeException;

/**
 * Class EasySms.
 */
class EasySms
{
    /**
     * @var \Overtrue\EasySms\Support\Config
     */
    protected $config;

    /**
     * @var string
     */
    protected $defaultGateway;

    /**
     * @var array
     */
    protected $customCreators = [];

    /**
     * @var array
     */
    protected $gateways = [];

    /**
     * @var \Overtrue\EasySms\Messenger
     */
    protected $messenger;

    /**
     * @var array
     */
    protected $strategies = [];

    /**
     * Constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = new Config($config);

        if (!empty($config['default'])) {
            $this->setDefaultGateway($config['default']);
        }
    }

    /**
     * Send a message.
     *
     * @param string|array                                 $to
     * @param \Overtrue\EasySms\Contracts\MessageInterface $message
     * @param array                                        $gateways
     *
     * @return array
     */
    public function send($to, $message, array $gateways = [])
    {
        return $this->getMessenger()->send($to, $message, $gateways);
    }

    /**
     * Create a gateway.
     *
     * @param string $name
     *
     * @return \Overtrue\EasySms\Contracts\GatewayInterface
     */
    public function gateway($name = null)
    {
        $name = $name ?: $this->getDefaultGateway();

        if (!isset($this->gateways[$name])) {
            $this->gateways[$name] = $this->createGateway($name);
        }

        return $this->gateways[$name];
    }

    /**
     * Get a strategy instance.
     *
     * @param string|null $strategy
     *
     * @return StrategyInterface
     */
    public function strategy($strategy = null)
    {
        if (is_null($strategy)) {
            $strategy = $this->config->get('default.strategy', OrderStrategy::class);
        }

        if (!class_exists($strategy)) {
            $strategy = __NAMESPACE__.'\Strategies\\'.ucfirst($strategy);
        }

        if (!class_exists($strategy)) {
            throw new InvalidArgumentException("Unsupported strategy \"{$strategy}\"");
        }

        if (empty($this->strategies[$strategy]) || !($this->strategies[$strategy] instanceof StrategyInterface)) {
            $this->strategies[$strategy] = new $strategy($this);
        }

        return $this->strategies[$strategy];
    }

    /**
     * Register a custom driver creator Closure.
     *
     * @param string   $name
     * @param \Closure $callback
     *
     * @return $this
     */
    public function extend($name, Closure $callback)
    {
        $this->customCreators[$name] = $callback;

        return $this;
    }

    /**
     * @return \Overtrue\EasySms\Support\Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Get default gateway name.
     *
     * @return string
     *
     * @throws if no default gateway configured
     */
    public function getDefaultGateway()
    {
        if (empty($this->defaultGateway)) {
            throw new RuntimeException('No default gateway configured.');
        }

        return $this->defaultGateway;
    }

    /**
     * Set default gateway name.
     *
     * @param string $name
     *
     * @return $this
     */
    public function setDefaultGateway($name)
    {
        $this->defaultGateway = $name;

        return $this;
    }

    /**
     * @return \Overtrue\EasySms\Messenger
     */
    public function getMessenger()
    {
        return $this->messenger ?: $this->messenger = new Messenger($this);
    }

    /**
     * Create a new driver instance.
     *
     * @param string $name
     *
     * @throws \InvalidArgumentException
     *
     * @return GatewayInterface
     */
    protected function createGateway($name)
    {
        if (isset($this->customCreators[$name])) {
            $gateway = $this->callCustomCreator($name);
        } else {
            $className = $this->formatGatewayClassName($name);
            $gateway = $this->makeGateway($className, $this->config->get("gateways.{$name}", []));
        }

        if (!($gateway instanceof GatewayInterface)) {
            throw new InvalidArgumentException(sprintf('Gateway "%s" not inherited from %s.', $name, GatewayInterface::class));
        }

        return $gateway;
    }

    /**
     * Make gateway instance.
     *
     * @param string $gateway
     * @param array  $config
     *
     * @return \Overtrue\EasySms\Contracts\GatewayInterface
     */
    protected function makeGateway($gateway, $config)
    {
        if (!class_exists($gateway)) {
            throw new InvalidArgumentException(sprintf('Gateway "%s" not exists.', $gateway));
        }

        return new $gateway($config);
    }

    /**
     * Format gateway name.
     *
     * @param string $name
     *
     * @return string
     */
    protected function formatGatewayClassName($name)
    {
        if (class_exists($name)) {
            return $name;
        }

        $name = ucfirst(str_replace(['-', '_', ''], '', $name));

        return __NAMESPACE__."\\Gateways\\{$name}Gateway";
    }

    /**
     * Call a custom gateway creator.
     *
     * @param string $gateway
     *
     * @return mixed
     */
    protected function callCustomCreator($gateway)
    {
        return call_user_func($this->customCreators[$gateway], $this->config->get($gateway, []));
    }
}
