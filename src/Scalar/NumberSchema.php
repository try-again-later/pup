<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup\Scalar;

use TryAgainLater\Pup\Rules\NumberRules;
use TryAgainLater\Pup\Scalar\ScalarSchema;
use TryAgainLater\Pup\Util\ValueWithErrors;

use function PHPUnit\Framework\isReadable;

/**
 * Base schema both for floats and ints.
 */
abstract class NumberSchema extends ScalarSchema
{
    private int | float | null $min = null;
    private ?bool $minIsReachable = null;

    private int | float | null $max = null;
    private ?bool $maxIsReachable = null;

    public function validate(mixed $value = null, bool $nothing = false): ValueWithErrors
    {
        $withErrors = parent::validate($value, nothing: func_num_args() === 0 || $nothing);

        return $withErrors
            ->next(NumberRules::min(
                enable: isset($this->min),
                value: $this->min ?? 0,
                isReachable: $this->minIsReachable ?? true,
            ))
            ->next(NumberRules::max(
                enable: isset($this->max),
                value: $this->max ?? 0,
                isReachable: $this->maxIsReachable ?? true,
            ));
    }

    public function min(int | float $min): static
    {
        $newSchema = clone $this;
        $newSchema->min = $min;
        $newSchema->minIsReachable = true;
        return $newSchema;
    }

    public function max(int | float $max): static
    {
        $newSchema = clone $this;
        $newSchema->max = $max;
        $newSchema->maxIsReachable = true;
        return $newSchema;
    }

    public function smallerThan(int | float $max): static
    {
        $newSchema = clone $this;
        $newSchema->max = $max;
        $newSchema->maxIsReachable = false;
        return $newSchema;
    }

    public function greaterThan(int | float $min): static
    {
        $newSchema = clone $this;
        $newSchema->min = $min;
        $newSchema->minIsReachable = false;
        return $newSchema;
    }

    public function positive(): static
    {
        return $this->greaterThan(0);
    }

    public function negative(): static
    {
        return $this->smallerThan(0);
    }
}
