<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup;

use TryAgainLater\Pup\Rules\AssociativeArrayRules;
use TryAgainLater\Pup\Util\ValueWithErrors;

class AssociativeArraySchema extends Schema
{
    /**
     * @param array<string, Schema> $shape
     */
    public function __construct(
        private array $shape,
        SchemaParameters $schemaParameters = new SchemaParameters(),
    )
    {
        parent::__construct(
            checkType: AssociativeArrayRules::checkType(),
            schemaParameters: $schemaParameters,
        );
    }

    public function validate(mixed $value = null, bool $nothing = false): ValueWithErrors
    {
        $withErrors = parent::validate($value, nothing: func_num_args() === 0 || $nothing);

        return $withErrors
            ->next(AssociativeArrayRules::validateShape($this->shape));
    }
}
