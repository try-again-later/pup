<?php

declare(strict_types = 1);

namespace TryAgainLater\Pup\Attributes;

use Attribute;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionProperty;

use TryAgainLater\Pup\AssociativeArraySchema;
use TryAgainLater\Pup\Attributes\Generic\{
    ParsedProperty,
    AllowCoercions,
    OneOf,
    ReplaceNullWithDefault,
    Required,
    Test,
    Transform,
};
use TryAgainLater\Pup\Attributes\String\{MinLength, MaxLength, Length};
use TryAgainLater\Pup\Attributes\Number\{Min, Max, GreaterThan, Negative, Positive, SmallerThan};
use TryAgainLater\Pup\Scalar\{NumberSchema, ScalarSchema, StringSchema};
use TryAgainLater\Pup\{Schema, SchemaParameters};

#[Attribute(Attribute::TARGET_CLASS)]
class FromAssociativeArray
{
    private static function hasAttribute(
        ReflectionProperty $reflectionProperty,
        string $attributeClass,
    ): bool
    {
        return !empty($reflectionProperty->getAttributes($attributeClass));
    }

    /**
     * @template T
     * @param class-string<T> $attributeClass
     *
     * @return T
     */
    private static function getInstance(
        ReflectionProperty $reflectionProperty,
        string $attributeClass,
    )
    {
        return $reflectionProperty->getAttributes($attributeClass)[0]->newInstance();
    }

    public static function setStringSchemaOptions(
        ReflectionProperty $reflectionProperty,
        StringSchema $schema,
    ): StringSchema
    {
        if (self::hasAttribute($reflectionProperty, MinLength::class)) {
            $minAttributeInstance = self::getInstance($reflectionProperty, MinLength::class);
            $schema = $schema->min($minAttributeInstance->value);
        }

        if (self::hasAttribute($reflectionProperty, MaxLength::class)) {
            $maxAttributeInstance = self::getInstance($reflectionProperty, MaxLength::class);
            $schema = $schema->max($maxAttributeInstance->value);
        }

        if (self::hasAttribute($reflectionProperty, Length::class)) {
            $lengthAttributeInstance = self::getInstance($reflectionProperty, Length::class);
            $schema = $schema->length($lengthAttributeInstance->value);
        }

        return $schema;
    }

    public static function setNumberSchemaOptions(
        ReflectionProperty $reflectionProperty,
        NumberSchema $schema,
    ): NumberSchema
    {
        if (self::hasAttribute($reflectionProperty, Min::class)) {
            $minAttributeInstance = self::getInstance($reflectionProperty, Min::class);
            $schema = $schema->min($minAttributeInstance->value);
        }

        if (self::hasAttribute($reflectionProperty, Max::class)) {
            $maxAttributeInstance = self::getInstance($reflectionProperty, Max::class);
            $schema = $schema->max($maxAttributeInstance->value);
        }

        if (self::hasAttribute($reflectionProperty, GreaterThan::class)) {
            $greaterThanInstance = self::getInstance($reflectionProperty, GreaterThan::class);
            $schema = $schema->greaterThan($greaterThanInstance->value);
        }

        if (self::hasAttribute($reflectionProperty, SmallerThan::class)) {
            $smallerThanInstance = self::getInstance($reflectionProperty, SmallerThan::class);
            $schema = $schema->smallerThan($smallerThanInstance->value);
        }

        if (self::hasAttribute($reflectionProperty, Negative::class)) {
            $schema = $schema->negative();
        }

        if (self::hasAttribute($reflectionProperty, Positive::class)) {
            $schema = $schema->positive();
        }

        return $schema;
    }

    public static function setScalarSchemaOptions(
        ReflectionProperty $reflectionProperty,
        ScalarSchema $schema,
    ): ScalarSchema
    {
        if (self::hasAttribute($reflectionProperty, OneOf::class)) {
            $oneOfAttributeInstance = self::getInstance($reflectionProperty, OneOf::class);
            $schema = $schema->oneOf(...$oneOfAttributeInstance->values);
        }

        return $schema;
    }

    /**
     * @param class-string $class
     */
    public static function schemaFromClass(string $class): AssociativeArraySchema
    {
        $reflectionClass = new ReflectionClass($class);

        $classAttributes = $reflectionClass->getAttributes(FromAssociativeArray::class);
        if (empty($classAttributes)) {
            throw new InvalidArgumentException(
                "'$class' is expected to have FromAssociativeArray attribute."
            );
        }
        $classAttributeInstance = $classAttributes[0]->newInstance();

        $shape = [];

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $propertyAttributes = $reflectionProperty->getAttributes(ParsedProperty::class);
            if (empty($propertyAttributes)) {
                continue;
            }
            $propertyAttributeInstance = $propertyAttributes[0]->newInstance();

            $type = strval($reflectionProperty->getType());
            if (str_starts_with($type, '?')) {
                $type = substr($type, 1);
            }

            $schemaParameters = new SchemaParameters(
                required: self::hasAttribute($reflectionProperty, Required::class),
                nullable: $reflectionProperty->getType()?->allowsNull() ?? false,
                replaceNullWithDefault:
                    self::hasAttribute($reflectionProperty, ReplaceNullWithDefault::class),
                allowCoercions: self::hasAttribute($reflectionProperty, AllowCoercions::class),
            );

            if ($type == 'int') {
                $schema = Schema::int($schemaParameters);
                $schema = self::setNumberSchemaOptions($reflectionProperty, $schema);
                $schema = self::setScalarSchemaOptions($reflectionProperty, $schema);
            } else if ($type == 'float') {
                $schema = Schema::float($schemaParameters);
                $schema = self::setNumberSchemaOptions($reflectionProperty, $schema);
                $schema = self::setScalarSchemaOptions($reflectionProperty, $schema);
            } else if ($type == 'string') {
                $schema = Schema::string($schemaParameters);
                $schema = self::setStringSchemaOptions($reflectionProperty, $schema);
                $schema = self::setScalarSchemaOptions($reflectionProperty, $schema);
            } else if ($type == 'bool') {
                $schema = Schema::bool($schemaParameters);
            } else {
                continue;
            }

            if ($reflectionProperty->hasDefaultValue()) {
                $schema = $schema->default($reflectionProperty->getDefaultValue());
            }

            foreach ($reflectionProperty->getAttributes(Transform::class) as $transformAttribute) {
                $transformInstance = $transformAttribute->newInstance();
                $schema = $schema->transform(...$transformInstance->transforms);
            }

            foreach ($reflectionProperty->getAttributes(Test::class) as $testAttribute) {
                $testInstance = $testAttribute->newInstance();
                $schema = $schema->test(
                    $testInstance->name,
                    $testInstance->check,
                    $testInstance->message,
                    $testInstance->shortCircuit,
                );
            }

            $arrayKey = $propertyAttributeInstance->name ?? $reflectionProperty->getName();
            $shape[$arrayKey] = $schema;
        }

        return Schema::associativeArray($shape);
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @param array<string, mixed> $array
     *
     * @return array{null|T, list<string|array{string, string}>}
     */
    public static function tryInstance(
        string $class,
        array $array,
        ?AssociativeArraySchema $schema = null,
    ): array
    {
        if (!isset($schema)) {
            $schema = self::schemaFromClass($class);
        }
        $arrayWithErrors = $schema->validate($array);
        if ($arrayWithErrors->hasErrors() || !$arrayWithErrors->hasValue()) {
            return [null, $arrayWithErrors->errors()];
        }
        $validatedArray = $arrayWithErrors->value();

        $reflectionClass = new ReflectionClass($class);
        $instance = $reflectionClass->newInstance();

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $propertyAttributes = $reflectionProperty->getAttributes(ParsedProperty::class);
            if (empty($propertyAttributes)) {
                continue;
            }
            $propertyAttributeInstance = $propertyAttributes[0]->newInstance();

            $arrayKey = $propertyAttributeInstance->name ?? $reflectionProperty->getName();
            if (array_key_exists($arrayKey, $validatedArray)) {
                $reflectionProperty->setValue($instance, $validatedArray[$arrayKey]);
            }
        }
        return [$instance, $arrayWithErrors->errors()];
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @param array<string, mixed> $array
     *
     * @return T
     */
    public static function instance(
        string $class,
        array $array,
        ?AssociativeArraySchema $schema = null,
    )
    {
        [$instance, $errors] = self::tryInstance($class, $array, $schema);
        if (count($errors) > 0) {
            $message = 'Errors while parsing associative array:' . PHP_EOL;
            $message .= implode(PHP_EOL, array_map(
                static function ($keyError) {
                    if (is_string($keyError)) {
                        return $keyError;
                    } else {
                        [$key, $error] = $keyError;
                        return "[$key] => $error";
                    }
                },
                $errors,
            ));

            throw new InvalidArgumentException($message);
        }

        assert(!is_null($instance));

        return $instance;
    }
}
