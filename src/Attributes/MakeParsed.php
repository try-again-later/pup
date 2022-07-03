<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup\Attributes;

trait MakeParsed
{
    public static function from(array $array)
    {
        return FromAssociativeArray::instance(self::class, $array);
    }

    public static function tryFrom(array $array)
    {
        return FromAssociativeArray::tryInstance(self::class, $array);
    }
}
