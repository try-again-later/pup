<?php

declare(strict_types = 1);

use TryAgainLater\Pup\Schema;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class StringSchemaTest extends TestCase
{
    #[Test]
    public function Length_SatisfiedByExactLength()
    {
        $string = '12345';
        $schema = Schema::string()
            ->length(5);

        [$_, $errors] = $schema->validate($string)->get();

        $this->assertCount(0, $errors);
    }

    #[Test]
    public function Length_NotSatisfiedByDifferentLength()
    {
        $string = '12345';
        $schema = Schema::string()
            ->length(6);

        [$_, $errors] = $schema->validate($string)->get();

        $this->assertNotCount(0, $errors);
    }

    #[Test]
    public function AllowCoercions_WorksForBools()
    {
        $value = true;
        $schema = Schema::string()
            ->allowCoercions();

        [$validatedValue, $errors] = $schema->validate($value)->get();

        $this->assertEquals('true', $validatedValue);
        $this->assertCount(0, $errors);
    }

    #[Test]
    public function AllowCoercions_WorksForNumbers()
    {
        $value = 42;
        $schema = Schema::string()
            ->allowCoercions();

        [$validatedValue, $errors] = $schema->validate($value)->get();

        $this->assertEquals('42', $validatedValue);
        $this->assertCount(0, $errors);
    }

    #[Test]
    public function CoercionsFail_WhenNotAllowedExplicitly()
    {
        $value = true;
        $schema = Schema::string();

        $withErrors = $schema->validate($value);

        $this->assertTrue($withErrors->hasErrors());
    }
}
