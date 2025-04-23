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

use Overtrue\EasySms\Contracts\GatewayInterface;
use Overtrue\EasySms\Contracts\MessageInterface;

/**
 * Class Message.
 */
class Message implements MessageInterface
{
    protected array $gateways = [];

    protected string $type;

    protected \Closure|string|null $content = null;

    protected \Closure|string|null $template = null;

    protected array|\Closure $data = [];

    /**
     * Message constructor.
     */
    public function __construct(array $attributes = [], string $type = MessageInterface::TEXT_MESSAGE)
    {
        $this->type = $type;

        foreach ($attributes as $property => $value) {
            if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }
    }

    /**
     * Return the message type.
     */
    public function getMessageType(): string
    {
        return $this->type;
    }

    /**
     * Return message content.
     */
    public function getContent(?GatewayInterface $gateway = null): ?string
    {
        return is_callable($this->content) ? call_user_func($this->content, $gateway) : $this->content;
    }

    /**
     * Return the template id of message.
     */
    public function getTemplate(?GatewayInterface $gateway = null): ?string
    {
        return is_callable($this->template) ? call_user_func($this->template, $gateway) : $this->template;
    }

    /**
     * @return $this
     */
    public function setType($type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return $this
     */
    public function setContent($content): static
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return $this
     */
    public function setTemplate($template): static
    {
        $this->template = $template;

        return $this;
    }

    public function getData(?GatewayInterface $gateway = null): array
    {
        return is_callable($this->data) ? call_user_func($this->data, $gateway) : $this->data;
    }

    /**
     * @return $this
     */
    public function setData(callable|array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function getGateways(): array
    {
        return $this->gateways;
    }

    /**
     * @return $this
     */
    public function setGateways(array $gateways): static
    {
        $this->gateways = $gateways;

        return $this;
    }

    /**
     * @return string
     */
    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }
}
