<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup\Attributes\Generic;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class OneOf
{
    public array $values;

    public function __construct(...$values)
    {
        $this->values = $values;
    }
}
