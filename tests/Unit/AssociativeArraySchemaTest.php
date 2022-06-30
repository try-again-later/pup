<?php

declare(strict_types = 1);

use PHPUnit\Framework\TestCase;
use TryAgainLater\Pup\Schema;

class AssociativeArraySchemaTest extends TestCase
{
    public function test_NoValidationErrors_GivenCorrectArray()
    {
        $array = [
            'first' => 42,
            'second' => -0.25,
            'third' => 'some string',
        ];

        $schema = Schema::associativeArray([
            'first' => Schema::int()
                ->required()
                ->positive()
                ->smallerThan(100),

            'second' => Schema::float()
                ->required()
                ->negative()
                ->greaterThan(-100),

            'third' => Schema::string()
                ->required()
                ->min(3)
                ->max(20),
        ]);

        $withErrors = $schema->validate($array);

        $this->assertFalse($withErrors->hasErrors());
    }
}
