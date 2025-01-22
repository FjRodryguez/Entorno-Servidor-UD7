<?php

declare(strict_types=1);

namespace Com\Daw2\Libraries;

class Respuesta
{
    public function __construct(
        private int $code,
        private ?array $data = null
    ){}

    public function getCode(): int
    {
        return $this->code;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function hasData()
    {
        return !is_null($this->data);
    }

}