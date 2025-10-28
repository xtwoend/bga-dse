<?php

declare(strict_types=1);

namespace App\Mqtt\Contract;

interface MqttHandlerInterface
{
    public function handle(string $topic, string $message): void;
}