<?php

/*
 * This file is part of the overtrue/easy-sms.
 * (c) overtrue <i@overtrue.me>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\EasySms\Contracts;

/**
 * Interface MessageInterface.
 */
interface MessageInterface
{
    const TEXT_MESSAGE = 'text';
    const VOICE_MESSAGE = 'voice';

    /**
     * Return the message type.
     *
     * @return string
     */
    public function getMessageType();

    /**
     * Return message content.
     *
     * @return string
     */
    public function getContent();

    /**
     * Return the template id of message.
     *
     * @return string
     */
    public function getTemplate();

    /**
     * Return the template data of message.
     *
     * @return string
     */
    public function getData();

    /**
     * Return message supported gateways.
     *
     * @return array
     */
    public function getGateways();
}
