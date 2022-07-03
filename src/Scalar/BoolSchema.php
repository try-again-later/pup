<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup\Scalar;

use TryAgainLater\Pup\Rules\BoolRules;
use TryAgainLater\Pup\SchemaParameters;

class BoolSchema extends ScalarSchema
{
    public function __construct(SchemaParameters $schemaParameters = new SchemaParameters())
    {
        parent::__construct(
            checkType: BoolRules::checkType(),
            coerceToType: BoolRules::coerceToType(),
            schemaParameters: $schemaParameters,
        );
    }
}
