<?php

/*
 * This file is part of the overtrue/easy-sms.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\EasySms;

use Closure;
use Overtrue\EasySms\Contracts\GatewayInterface;
use Overtrue\EasySms\Contracts\MessageInterface;
use Overtrue\EasySms\Contracts\PhoneNumberInterface;
use Overtrue\EasySms\Contracts\StrategyInterface;
use Overtrue\EasySms\Exceptions\InvalidArgumentException;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;
use Overtrue\EasySms\Gateways\Gateway;
use Overtrue\EasySms\Strategies\OrderStrategy;
use Overtrue\EasySms\Support\Config;

/**
 * Class EasySms.
 */
class EasySms
{
    protected Config $config;

    protected string $defaultGateway;

    protected array $customCreators = [];

    protected array $gateways = [];

    protected Messenger $messenger;

    protected array $strategies = [];

    /**
     * Constructor.
     */
    public function __construct(array $config)
    {
        $this->config = new Config($config);
    }

    /**
     * Send a message.
     *
     * @throws NoGatewayAvailableException
     * @throws InvalidArgumentException
     */
    public function send(array|string $to, MessageInterface|array $message, array $gateways = []): array
    {
        $to = $this->formatPhoneNumber($to);
        $message = $this->formatMessage($message);
        $gateways = empty($gateways) ? $message->getGateways() : $gateways;

        if (empty($gateways)) {
            $gateways = $this->config->get('default.gateways', []);
        }

        return $this->getMessenger()->send($to, $message, $this->formatGateways($gateways));
    }

    /**
     * Create a gateway.
     *
     * @throws InvalidArgumentException
     */
    public function gateway(?string $name): GatewayInterface
    {
        if (!isset($this->gateways[$name])) {
            $this->gateways[$name] = $this->createGateway($name);
        }

        return $this->gateways[$name];
    }

    /**
     * Get a strategy instance.
     *
     * @throws InvalidArgumentException
     */
    public function strategy(?string $strategy = null): StrategyInterface
    {
        if (\is_null($strategy)) {
            $strategy = $this->config->get('default.strategy', OrderStrategy::class);
        }

        if (!\class_exists($strategy)) {
            $strategy = __NAMESPACE__ . '\Strategies\\' . \ucfirst($strategy);
        }

        if (!\class_exists($strategy)) {
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
     * @return $this
     */
    public function extend(string $name, \Closure $callback): static
    {
        $this->customCreators[$name] = $callback;

        return $this;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function getMessenger(): Messenger
    {
        return $this->messenger ??= new Messenger($this);
    }

    /**
     * Create a new driver instance.
     *
     * @throws \InvalidArgumentException
     * @throws InvalidArgumentException
     */
    protected function createGateway(string $name): GatewayInterface
    {
        $config = $this->config->get("gateways.{$name}", []);

        if (!isset($config['timeout'])) {
            $config['timeout'] = $this->config->get('timeout', Gateway::DEFAULT_TIMEOUT);
        }

        $config['options'] = $this->config->get('options', []);

        if (isset($this->customCreators[$name])) {
            $gateway = $this->callCustomCreator($name, $config);
        } else {
            $className = $this->formatGatewayClassName($name);
            $gateway = $this->makeGateway($className, $config);
        }

        if (!($gateway instanceof GatewayInterface)) {
            throw new InvalidArgumentException(\sprintf('Gateway "%s" must implement interface %s.', $name, GatewayInterface::class));
        }

        return $gateway;
    }

    /**
     * Make gateway instance.
     *
     * @throws InvalidArgumentException
     */
    protected function makeGateway(string $gateway, array $config): GatewayInterface
    {
        if (!\class_exists($gateway) || !\in_array(GatewayInterface::class, \class_implements($gateway))) {
            throw new InvalidArgumentException(\sprintf('Class "%s" is a invalid easy-sms gateway.', $gateway));
        }

        return new $gateway($config);
    }

    /**
     * Format gateway name.
     */
    protected function formatGatewayClassName(string $name): string
    {
        if (\class_exists($name) && \in_array(GatewayInterface::class, \class_implements($name))) {
            return $name;
        }

        $name = \ucfirst(\str_replace(['-', '_', ''], '', $name));

        return __NAMESPACE__ . "\\Gateways\\{$name}Gateway";
    }

    /**
     * Call a custom gateway creator.
     */
    protected function callCustomCreator(string $gateway, array $config): mixed
    {
        return \call_user_func($this->customCreators[$gateway], $config);
    }

    protected function formatPhoneNumber(PhoneNumberInterface|string $number): PhoneNumberInterface
    {
        if ($number instanceof PhoneNumberInterface) {
            return $number;
        }

        return new PhoneNumber(\trim($number));
    }

    protected function formatMessage(MessageInterface|array|string $message): MessageInterface
    {
        if (!($message instanceof MessageInterface)) {
            if (!\is_array($message)) {
                $message = [
                    'content'  => $message,
                    'template' => $message,
                ];
            }

            $message = new Message($message);
        }

        return $message;
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function formatGateways(array $gateways): array
    {
        $formatted = [];

        foreach ($gateways as $gateway => $setting) {
            if (\is_int($gateway) && \is_string($setting)) {
                $gateway = $setting;
                $setting = [];
            }

            $formatted[$gateway] = $setting;
            $globalSettings = $this->config->get("gateways.{$gateway}", []);

            if (\is_string($gateway) && !empty($globalSettings) && \is_array($setting)) {
                $formatted[$gateway] = new Config(\array_merge($globalSettings, $setting));
            }
        }

        $result = [];

        foreach ($this->strategy()->apply($formatted) as $name) {
            $result[$name] = $formatted[$name];
        }

        return $result;
    }
}