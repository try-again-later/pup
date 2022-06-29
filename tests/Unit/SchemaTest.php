<?php

declare (strict_types = 1);

use TryAgainLater\Pup\Schema;

use PHPUnit\Framework\TestCase;

class SchemaTest extends TestCase
{
    public function test_Transform_SingleTransformWorksCorrectly()
    {
        $value = 'foo';
        $schema = Schema::string()->transform(fn ($string) => $string === 'foo' ? 'bar' : 'baz');

        $validatedValue = $schema->validate($value)->value();

        $this->assertEquals('bar', $validatedValue);
    }

    public function test_Transform_MultipleTransformsWorkCorrectly()
    {
        $value = 'initial';
        $schema = Schema::string()
            ->transform(fn ($string) => $string === 'initial' ? 'after first transform' : 'error')
            ->transform(fn ($string) =>
                $string === 'after first transform' ? 'after second transform' : 'error');

        $validatedValue = $schema->validate($value)->value();

        $this->assertEquals('after second transform', $validatedValue);
    }

    public function test_ReplaceNullWithDefault_WorksForNull()
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
