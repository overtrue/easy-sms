<?php

/*
 * This file is part of the overtrue/easy-sms.
 * (c) overtrue <i@overtrue.me>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\EasySms;

use Overtrue\EasySms\Contracts\MessageInterface;

/**
 * Class Message.
 */
class Message implements MessageInterface
{
    /**
     * @var array
     */
    protected $gateways = [];

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $content;

    /**
     * @var string
     */
    protected $template;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * Message constructor.
     *
     * @param array  $attributes
     * @param string $type
     */
    public function __construct(array $attributes = [], $type = MessageInterface::TEXT_MESSAGE)
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
     *
     * @return string
     */
    public function getMessageType()
    {
        return $this->type;
    }

    /**
     * Return message content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Return the template id of message.
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType(string $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @param mixed $content
     *
     * @return $this
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @param mixed $template
     *
     * @return $this
     */
    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     *
     * @return $this
     */
    public function setData(array $data)
    {
        $this->data = $data;s

        return $this;
    }

    /**
     * @return array
     */
    public function getGateways()
    {
        return $this->gateways;
    }

    /**
     * @param array $gateways
     *
     * @return $this
     */
    public function setGateways(array $gateways)
    {
        $this->gateways = $gateways;

        return $this;
    }

    /**
     * @param $property
     *
     * @return string
     */
    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }
}
