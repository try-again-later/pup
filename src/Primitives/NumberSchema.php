<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup\Primitives;

use TryAgainLater\Pup\Primitives\ScalarSchema;
use TryAgainLater\Pup\Util\ValueWithErrors;

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
            ->next($this->validateMin(...))
            ->next($this->validateMax(...));
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

    protected function validateMin(ValueWithErrors $withErrors): ValueWithErrors
    {
        $error =
            'The number must be greater than ' .
            ($this->minIsReachable ? 'or equal to ' : '') .
            "$this->min.";

        return $withErrors->pushErrorIf(
            if: fn ($number) =>
                isset($this->min) &&
                ($number < $this->min || $number === $this->min && !$this->minIsReachable),
            error: $error,
        );
    }

    protected function validateMax(ValueWithErrors $withErrors): ValueWithErrors
    {
        $error =
            'The number must be smaller than ' .
            ($this->maxIsReachable ? 'or equal to ' : '') .
            "$this->max.";

        return $withErrors->pushErrorIf(
            if: fn ($number) =>
                isset($this->max) &&
                ($number > $this->max || $number === $this->max && !$this->maxIsReachable),
            error: $error,
        );
    }
}
