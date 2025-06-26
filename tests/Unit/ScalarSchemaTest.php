<?php

declare(strict_types = 1);

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use TryAgainLater\Pup\Schema;

class ScalarSchemaTest extends TestCase
{
    #[Test]
    public function oneOfDoesNotProduceError_whenPassedAllowedValue()
    {
        $value = 'foo';
        $schema = Schema::string()->oneOf('foo', 'bar', 'baz');

        $withErrors = $schema->validate($value);

        $this->assertFalse($withErrors->hasErrors());
    }

    #[Test]
    public function oneOfProducesError_whenPassedForbiddenValue()
    {
        $value = 'something forbidden';
        $schema = Schema::string()->oneOf('foo', 'bar', 'baz');

        $withErrors = $schema->validate($value);

        $this->assertTrue($withErrors->hasErrors());
    }
}
