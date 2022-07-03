<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup\Scalar;

use TryAgainLater\Pup\Rules\ScalarRules;
use TryAgainLater\Pup\Schema;
use TryAgainLater\Pup\Util\ValueWithErrors;

abstract class ScalarSchema extends Schema
{
    private ?array $allowedValues = null;

    public function validate(mixed $value = null, bool $nothing = false): ValueWithErrors
    {
        $withErrors = parent::validate($value, nothing: func_num_args() === 0 || $nothing);

        return $withErrors
            ->next(ScalarRules::oneOf(isset($this->allowedValues), $this->allowedValues ?? []));
    }

    public function oneOf(mixed ...$allowedValues): static
    {
        $newSchema = clone $this;
        $newSchema->allowedValues = $allowedValues;
        return $newSchema;
    }
}
