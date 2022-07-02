<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup\Attributes\Generic;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ParsedProperty
{
    public function __construct(
        public ?string $name = null,
    )
    {}
}
