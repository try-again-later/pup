<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup\Attributes\Generic;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Transform
{
    public array $transforms;

    public function __construct(callable ...$transforms)
    {
        $this->transforms = $transforms;
    }
}
