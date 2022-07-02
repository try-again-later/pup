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
    ReplaceNullWithDefault,
    Required,
    Transform,
};
use TryAgainLater\Pup\Schema;
use TryAgainLater\Pup\SchemaParameters;

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

            $createSchema = match ($type) {
                'int' => fn (...$args) => Schema::int(...$args),
                'float' => fn (...$args) => Schema::float(...$args),
                'string' => fn (...$args) => Schema::string(...$args),
            };

            $schema = $createSchema(new SchemaParameters(
                required: self::hasAttribute($reflectionProperty, Required::class),
                nullable: $reflectionProperty->getType()->allowsNull(),
                replaceNullWithDefault:
                    self::hasAttribute($reflectionProperty, ReplaceNullWithDefault::class),
                allowCoercions: self::hasAttribute($reflectionProperty, AllowCoercions::class),
            ));

            if ($reflectionProperty->hasDefaultValue()) {
                $schema = $schema->default($reflectionProperty->getDefaultValue());
            }

            foreach ($reflectionProperty->getAttributes(Transform::class) as $transformAttribute) {
                $transformInstance = $transformAttribute->newInstance();
                $schema = $schema->transform(...$transformInstance->transforms);
            }

            $arrayKey = $propertyAttributeInstance->name ?? $reflectionProperty->getName();
            $shape[$arrayKey] = $schema;
        }

        return Schema::associativeArray($shape);
    }

    public static function instance(
        string $class,
        array $array,
        ?AssociativeArraySchema $schema = null,
    )
    {
        if (!isset($schema)) {
            $schema = self::schemaFromClass($class);
        }
        $arrayWithErrors = $schema->validate($array);
        if ($arrayWithErrors->hasErrors() || !$arrayWithErrors->hasValue()) {
            return false;
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
                $reflectionProperty->setAccessible(true);
                $reflectionProperty->setValue($instance, $validatedArray[$arrayKey]);
            }
        }
        return $instance;
    }
}
