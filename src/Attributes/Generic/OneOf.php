<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup\Attributes\Generic;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class OneOf
{
    /** @var list<int|float|string> */
    public array $values;

    /**
     * @param int|float|string ...$values
     */
    public function __construct(...$values)
    {
        $this->values = array_values($values);
    }
}
