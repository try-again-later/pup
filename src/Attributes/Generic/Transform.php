<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup\Attributes\Generic;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Transform
{
    /** @var list<callable(mixed): mixed> */
    public array $transforms;

    /**
     * @param callable(mixed): mixed ...$transforms
     */
    public function __construct(callable ...$transforms)
    {
        $this->transforms = array_values($transforms);
    }
}
