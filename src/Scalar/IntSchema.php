<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup\Scalar;

use TryAgainLater\Pup\Rules\IntRules;
use TryAgainLater\Pup\SchemaParameters;

class IntSchema extends NumberSchema
{
    public function __construct(SchemaParameters $schemaParameters = new SchemaParameters())
    {
        parent::__construct(
            checkType: IntRules::checkType(),
            coerceToType: IntRules::coerceToType(),
            schemaParameters: $schemaParameters,
        );
    }
}
