<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup\Scalar;

use TryAgainLater\Pup\Rules\FloatRules;
use TryAgainLater\Pup\SchemaParameters;

class FloatSchema extends NumberSchema
{
    public function __construct(SchemaParameters $schemaParameters = new SchemaParameters())
    {
        parent::__construct(
            checkType: FloatRules::checkType(),
            coerceToType: FloatRules::coerceToType(),
            schemaParameters: $schemaParameters,
        );
    }
}
