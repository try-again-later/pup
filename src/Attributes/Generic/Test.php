<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup\Attributes\Generic;

use Attribute;

#[Attribute]
class Test
{
    public string $name;
    public $check;
    public $message;
    public bool $shortCircuit;

    public function __construct(
        string $name,
        callable $check,
        callable | string $message,
        bool $shortCircuit = false,

    )
    {
        $this->name = $name;
        $this->check = $check;
        $this->message = $message;
        $this->shortCircuit = $shortCircuit;
    }
}
