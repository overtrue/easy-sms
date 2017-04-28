<?php

/*
 * This file is part of the overtrue/easy-sms.
 * (c) overtrue <i@overtrue.me>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\EasySms;

use Closure;
use InvalidArgumentException;
use Overtrue\EasySms\Contracts\GatewayInterface;
use Overtrue\EasySms\Contracts\MessageInterface;
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
     *
     * @return array
     */
    public function send($to, $message)
    {
        $messenger = $this->getMessenger();

        return $messenger->send($to, $message, $this->getMessageGateways($message));
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
     * @param \Overtrue\EasySms\Contracts\MessageInterface $message
     *
     * @return array
     */
    protected function getMessageGateways(MessageInterface $message)
    {
        $gateways = [];

        foreach ($message->getGateways() as $gateway => $setting) {
            if (is_integer($gateway) && is_string($setting)) {
                $gateway = $setting;
                $setting = [];
            }
            $globalSetting = $this->config->get("gateways.{$gateway}", []);

            if (is_string($gateway) && !empty($globalSetting)) {
                $gateways[$gateway] = array_merge($globalSetting, (array) $setting);
            }
        }

        if ($this->config->get('shuffle_gateways')) {
            uasort($gateways, function () {
                return mt_rand() - mt_rand();
            });
        }

        return $gateways;
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

    /**
     * Dynamically call the default gateway instance.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->gateway(), $method], $parameters);
    }
}
