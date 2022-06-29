<?php

declare(strict_types = 1);

use TryAgainLater\Pup\Primitives\StringSchema;
use TryAgainLater\Pup\Schema;

use PHPUnit\Framework\TestCase;

class StringSchemaTest extends TestCase
{
    public function test_Length_SatisfiedByExactLength()
    {
        $string = '12345';
        $schema = Schema::string()
            ->length(5);

        [$_, $errors] = $schema->validate($string)->get();

        $this->assertCount(0, $errors);
    }

    public function test_Length_NotSatisfiedByDifferentLength()
    {
        $string = '12345';
        $schema = Schema::string()
            ->length(6);

        [$_, $errors] = $schema->validate($string)->get();

        $this->assertNotCount(0, $errors);
    }
}
