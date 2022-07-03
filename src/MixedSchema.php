<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup\Scalar;

use TryAgainLater\Pup\Rules\MixedRules;
use TryAgainLater\Pup\SchemaParameters;

class MixedSchema extends ScalarSchema
{
    public function __construct(SchemaParameters $schemaParameters = new SchemaParameters())
    {
        parent::__construct(
            checkType: MixedRules::checkType(),
            schemaParameters: $schemaParameters,
        );
    }
}
