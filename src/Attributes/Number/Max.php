<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup\Attributes\Number;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Max
{
    public function __construct(public int $value)
    {}
}
