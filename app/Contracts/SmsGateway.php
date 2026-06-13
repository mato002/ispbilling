<?php

namespace App\Contracts;

use App\DataTransferObjects\SmsGatewayConfig;

interface SmsGateway
{
    public function send(string $to, string $message, SmsGatewayConfig $config): void;
}
