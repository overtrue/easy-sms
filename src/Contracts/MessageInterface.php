<?php

/*
 * This file is part of the overtrue/easy-sms.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\EasySms\Contracts;

/**
 * Interface MessageInterface.
 */
interface MessageInterface
{
    public const TEXT_MESSAGE = 'text';

    public const VOICE_MESSAGE = 'voice';

    /**
     * Return the message type.
     */
    public function getMessageType(): string;

    /**
     * Return message content.
     */
    public function getContent(?GatewayInterface $gateway = null): ?string;

    /**
     * Return the template id of message.
     */
    public function getTemplate(?GatewayInterface $gateway = null): ?string;

    /**
     * Return the template data of message.
     */
    public function getData(?GatewayInterface $gateway = null): array;

    /**
     * Return message supported gateways.
     */
    public function getGateways(): array;
}
