<?php

declare(strict_types = 1);

require __DIR__ . '/../vendor/autoload.php';

use TryAgainLater\Pup\Attributes\{FromAssociativeArray, MakeParsed};
use TryAgainLater\Pup\Attributes\Generic\{ParsedProperty, Required};
use TryAgainLater\Pup\Attributes\Number\Positive;
use TryAgainLater\Pup\Attributes\String\MaxLength;

#[FromAssociativeArray]
class User
{
    #[ParsedProperty]
    #[Required]
    #[MaxLength(8)]
    private string $name;

    #[ParsedProperty]
    #[Positive]
    private int $age;

    use MakeParsed;
}

$user = User::from([
    'name' => 'John',
    'age' => 42,
]);

// Throws an exception because of negative age:
$user = User::from([
    'name' => 'John',
    'age' => -42,
]);

// Throws an exception because the name is too long
$user = User::from([
    'name' => 'John Doe Blah Blah Blah',
    'age' => -42,
]);
