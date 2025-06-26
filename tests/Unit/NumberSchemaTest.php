<?php

declare(strict_types = 1);

use TryAgainLater\Pup\Schema;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class NumberSchemaTest extends TestCase
{
    #[Test]
    public function minIsSatisfied_whenPassedBoundaryNumber()
    {
        $number = 42;
        $schema = Schema::int()->min($number);

        $withErrors = $schema->validate($number);

        $this->assertFalse($withErrors->hasErrors());
    }

    #[Test]
    public function minIsSatisfied_whenPassedLargerNumber()
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

    #[Test]
    public function greaterThanIsSatisfied_whenPassedLargerNumber()
    {
        $schema = Schema::int()->greaterThan(40);

        $withErrors = $schema->validate(42);

        $this->assertFalse($withErrors->hasErrors());
    }

    #[Test]
    public function maxIsSatisfied_whenPassedBoundaryNumber()
    {
        $number = 42;
        $schema = Schema::int()->max($number);

        $withErrors = $schema->validate($number);

        $this->assertFalse($withErrors->hasErrors());
    }

    #[Test]
    public function maxIsSatisfied_whenPassedSmallerNumber()
    {
        $schema = Schema::int()->max(42);

        $withErrors = $schema->validate(40);

        $this->assertFalse($withErrors->hasErrors());
    }

    #[Test]
    public function smallerThanIsNotSatisfied_whenPassedBoundaryNumber()
    {
        $number = 42;
        $schema = Schema::int()
            ->smallerThan($number);

        $withErrors = $schema->validate($number);

        $this->assertTrue($withErrors->hasErrors());
    }

    #[Test]
    public function smallerThenIsSatisfied_whenPassedSmallerNumber()
    {
        $schema = Schema::int()->smallerThan(42);

        $withErrors = $schema->validate(40);

        $this->assertFalse($withErrors->hasErrors());
    }
}
