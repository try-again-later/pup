<?php

declare(strict_types = 1);

require __DIR__ . '/../vendor/autoload.php';

use TryAgainLater\Pup\Schema;

$userSchema = Schema::associativeArray([
    'name' => Schema::string()
        ->required()
        ->transform(fn ($name) => "NAME = $name"),

    'age' => Schema::int()
        ->positive(),

    'email' => Schema::string()
        ->required()
        ->max(255),

    'website' => Schema::string()
        ->default('No website'),

    'sex' => Schema::string()
        ->nullable()
        ->oneOf('male', 'female'),

    'allowSendingEmails' => Schema::bool()
        ->default(false)
        ->test(
            name: 'Is string bool',
            check: fn ($string) => in_array($string, ['true', 'false'], strict: true),
            message: 'Only "true" or "false" strings are allowed.',
            shortCircuit: true,
        )
        ->transform(fn ($string) => match ($string) {
            'true', => true,
            'false' => false,
        }),
]);

$user = [
    'name' => 'John',
    'age' => -42,
    'email' => 'john@example.com',
    'sex' => 'male',
    'allowSendingEmails' => 'true',
];

[$validatedUser, $errors] = $userSchema->validate($user)->tryGet();

echo 'Validated object:' . PHP_EOL;
print_r($validatedUser);

echo PHP_EOL . 'Errors:' . PHP_EOL;
print_r($errors);
