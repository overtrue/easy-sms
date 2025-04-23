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
use Overtrue\EasySms\Gateways\Gateway;
use Overtrue\EasySms\Strategies\OrderStrategy;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;

/**
 * Class EasySms.
 */
class EasySms
{
    /**
     * @var Config
     */
    protected Config $config;

    /**
     * @var string
     */
    protected string $defaultGateway;

    /**
     * @var array
     */
    protected array $customCreators = [];

    /**
     * @var array
     */
    protected array $gateways = [];

    /**
     * @var Messenger
     */
    protected Messenger $messenger;

    /**
     * @var array
     */
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
     * @param array|string $to
     * @param array|MessageInterface $message
     * @param array $gateways
     * @throws NoGatewayAvailableException
     * @throws InvalidArgumentException
     * @return array
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
     * @param string|null $name
     * @throws InvalidArgumentException
     * @return GatewayInterface
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
     * @param string|null $strategy
     * @throws InvalidArgumentException
     * @return StrategyInterface
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
     * @param string $name
     * @param Closure $callback
     * @return $this
     */
    public function extend(string $name, \Closure $callback): static
    {
        $this->customCreators[$name] = $callback;

        return $this;
    }

    /**
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * @return Messenger
     */
    public function getMessenger(): Messenger
    {
        return $this->messenger ?: $this->messenger = new Messenger($this);
    }

    /**
     * Create a new driver instance.
     *
     * @param string $name
     * @throws \InvalidArgumentException
     * @throws InvalidArgumentException
     * @return GatewayInterface
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
     * @param string $gateway
     * @param array $config
     * @throws InvalidArgumentException
     * @return GatewayInterface
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
     *
     * @param string $name
     * @return string
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
     *
     * @param string $gateway
     * @param array $config
     * @return mixed
     */
    protected function callCustomCreator(string $gateway, array $config): mixed
    {
        return \call_user_func($this->customCreators[$gateway], $config);
    }

    /**
     * @param string|PhoneNumberInterface $number
     * @return PhoneNumberInterface
     */
    protected function formatPhoneNumber(PhoneNumberInterface|string $number): PhoneNumberInterface
    {
        if ($number instanceof PhoneNumberInterface) {
            return $number;
        }

        return new PhoneNumber(\trim($number));
    }

    /**
     * @param array|string|MessageInterface $message
     * @return MessageInterface
     */
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
     * @param array $gateways
     * @throws InvalidArgumentException
     * @return array
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