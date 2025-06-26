<?php

declare (strict_types = 1);

use TryAgainLater\Pup\Schema;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SchemaTest extends TestCase
{
    #[Test]
    public function transform_singleTransformWorksCorrectly()
    {
        $value = 'foo';
        $schema = Schema::string()->transform(fn ($string) => $string === 'foo' ? 'bar' : 'baz');

        $validatedValue = $schema->validate($value)->value();

        $this->assertEquals('bar', $validatedValue);
    }

    #[Test]
    public function transform_multipleTransformsWorkCorrectly()
    {
        $value = 'initial';
        $schema = Schema::string()
            ->transform(fn ($string) => $string === 'initial' ? 'after first transform' : 'error')
            ->transform(fn ($string) =>
                $string === 'after first transform' ? 'after second transform' : 'error');

        $validatedValue = $schema->validate($value)->value();

        $this->assertEquals('after second transform', $validatedValue);
    }

    #[Test]
    public function replaceNullWithDefault_worksForNull()
    {
        $defaultValue = 'foo';
        $schema = Schema::string()
            ->nullable()
            ->default($defaultValue)
            ->replaceNullWithDefault();

        [$validatedValue, $errors] = $schema->validate(null)->get();

        $this->assertEquals($defaultValue, $validatedValue);
        $this->assertCount(0, $errors);
    }
}
