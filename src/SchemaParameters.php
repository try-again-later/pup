<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup;

class SchemaParameters
{
    public function __construct(
        public bool $required = false,
        public bool $nullable = false,
        public mixed $defaultValue = null,
        public bool $hasDefault = false,
        public bool $replaceNullWithDefault = false,
        public bool $allowCoercions = false,
        public array $userDefinedTransforms = [],
    )
    {}
}
