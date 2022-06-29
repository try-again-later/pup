<?php

declare(strict_types = 1);

use TryAgainLater\Pup\Schema;

use PHPUnit\Framework\TestCase;

class NumberSchemaTest extends TestCase
{
    public function test_MinIsSatisfied_WhenPassedBoundaryNumber()
    {
        $number = 42;
        $schema = Schema::int()->min($number);

        $withErrors = $schema->validate($number);

        $this->assertFalse($withErrors->hasErrors());
    }

    public function test_MinIsSatisfied_WhenPassedLargerNumber()
    {
        $schema = Schema::int()->min(40);

        $withErrors = $schema->validate(42);

        $this->assertFalse($withErrors->hasErrors());
    }

    public function test_GreaterThanIsNotSatisfied_WhenPassedByBoundaryNumber()
    {
        $number = 42;
        $schema = Schema::int()->greaterThan($number);

        $withErrors = $schema->validate($number);

        $this->assertTrue($withErrors->hasErrors());
    }

    public function test_GreaterThanIsSatisfied_WhenPassedLargerNumber()
    {
        $schema = Schema::int()->greaterThan(40);

        $withErrors = $schema->validate(42);

        $this->assertFalse($withErrors->hasErrors());
    }

    public function test_MaxIsSatisfied_WhenPassedBoundaryNumber()
    {
        $number = 42;
        $schema = Schema::int()->max($number);

        $withErrors = $schema->validate($number);

        $this->assertFalse($withErrors->hasErrors());
    }

    public function test_MaxIsSatisfied_WhenPassedSmallerNumber()
    {
        $schema = Schema::int()->max(42);

        $withErrors = $schema->validate(40);

        $this->assertFalse($withErrors->hasErrors());
    }

    public function test_SmallerThanIsNotSatisfied_WhenPassedBoundaryNumber()
    {
        $number = 42;
        $schema = Schema::int()
            ->smallerThan($number);

        $withErrors = $schema->validate($number);

        $this->assertTrue($withErrors->hasErrors());
    }

    public function test_SmallerThenIsSatisfied_WhenPassedSmallerNumber()
    {
        $schema = Schema::int()->smallerThan(42);

        $withErrors = $schema->validate(40);

        $this->assertFalse($withErrors->hasErrors());
    }
}
