<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup\Attributes\String;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class MaxLength
{
    public function __construct(public int $value)
    {}
}
