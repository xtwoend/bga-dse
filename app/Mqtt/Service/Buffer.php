<?php

namespace App\Mqtt\Service;


class Buffer
{
    protected array $data = [];

    public function add(string $tag, $value): void
    {
        $this->data[$tag] = $value;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function clear(): void
    {
        $this->data = [];
    }
}
