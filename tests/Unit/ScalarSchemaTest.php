<?php

declare(strict_types = 1);

use PHPUnit\Framework\TestCase;

use TryAgainLater\Pup\Schema;

class ScalarSchemaTest extends TestCase
{
    public function test_OneOfDoesNotProduceError_WhenPassedAllowedValue()
    {
        $value = 'foo';
        $schema = Schema::string()->oneOf('foo', 'bar', 'baz');

        $withErrors = $schema->validate($value);

        $this->assertFalse($withErrors->hasErrors());
    }

    public function test_OneOfProducesError_WhenPassedForbiddenValue()
    {
        $value = 'something forbidden';
        $schema = Schema::string()->oneOf('foo', 'bar', 'baz');

        $withErrors = $schema->validate($value);

        $this->assertTrue($withErrors->hasErrors());
    }
}
