<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup\Scalar;

use InvalidArgumentException;

use TryAgainLater\Pup\Rules\StringRules;
use TryAgainLater\Pup\Util\ValueWithErrors;
use TryAgainLater\Pup\Scalar\ScalarSchema;
use TryAgainLater\Pup\SchemaParameters;

class StringSchema extends ScalarSchema
{
    private ?int $exactLength = null;
    private ?int $minLength = null;
    private ?int $maxLength = null;

    public function __construct(SchemaParameters $schemaParameters = new SchemaParameters())
    {
        parent::__construct(
            checkType: StringRules::checkType(),
            coerceToType: StringRules::coerceToType(),
            schemaParameters: $schemaParameters,
        );
    }

    public function validate(mixed $value = null, bool $nothing = false): ValueWithErrors
    {
        $withErrors = parent::validate($value, nothing: func_num_args() === 0 || $nothing);

        return $withErrors
            ->next(StringRules::exactLength(
                enabled: isset($this->exactLength),
                length: $this->exactLength ?? 0,
            ))
            ->next(StringRules::minLength(
                enabled: isset($this->minLength),
                length: $this->minLength ?? 0,
            ))
            ->next(StringRules::maxLength(
                enabled: isset($this->maxLength),
                length: $this->maxLength ?? 0,
            ));
    }

    public function length(int $length): static
    {
        self::assertRequiredStringLength($length);

        $newSchema = clone $this;
        $newSchema->exactLength = $length;
        return $newSchema;
    }

    public function min(int $minLength): static
    {
        self::assertRequiredStringLength($minLength);

        $newSchema = clone $this;
        $newSchema->minLength = $minLength;
        return $newSchema;
    }

    public function max(int $maxLength): static
    {
        self::assertRequiredStringLength($maxLength);

        $newSchema = clone $this;
        $newSchema->maxLength = $maxLength;
        return $newSchema;
    }

    private static function assertRequiredStringLength(int $stringLength): void
    {
        if ($stringLength < 0) {
            throw new InvalidArgumentException(
                "Requried string length cannot be a negative number (got '$stringLength')."
            );
        }
    }
}
