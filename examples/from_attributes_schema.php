<?php

declare(strict_types = 1);

require __DIR__ . '/../vendor/autoload.php';

use TryAgainLater\Pup\Attributes\{FromAssociativeArray, MakeParsed};
use TryAgainLater\Pup\Attributes\Generic\{OneOf, ParsedProperty, Required, Transform, Test};
use TryAgainLater\Pup\Attributes\Number\Positive;
use TryAgainLater\Pup\Attributes\String\MaxLength;

#[FromAssociativeArray]
class User
{
    public static function transformName(string $name): string
    {
        return "NAME = $name";
    }

    #[ParsedProperty]
    #[Required]
    #[Transform([self::class, 'transformName'])]
    private string $name;

    #[ParsedProperty]
    #[Positive]
    private int $age;

    #[ParsedProperty]
    #[Required]
    #[MaxLength(255)]
    private string $email;

    #[ParsedProperty]
    private string $website = 'No website';

    #[ParsedProperty]
    #[OneOf('male', 'female')]
    private ?string $sex = null;

    public static function validateBoolString(string $string): bool
    {
        return in_array($string, ['true', 'false'], strict: true);
    }

    public static function stringToBool(string $string): bool
    {
        return match ($string) {
            'true', => true,
            'false' => false,
        };
    }

    #[ParsedProperty('emails')]
    #[Test(
        name: 'Is string bool',
        check: [self::class, 'validateBoolString'],
        message: 'Only "true" or "false" strings are allowed.',
        shortCircuit: true,
    )]
    #[Transform([self::class, 'stringToBool'])]
    private bool $allowSendingEmails = false;

    use MakeParsed;
}

$user = User::from([
    'name' => 'John',
    'age' => 42,
    'email' => 'john@example.com',
    'sex' => 'male',
    'emails' => 'true',
]);

var_dump($user);

// Throws InvalidArgumentException with the following message:
// "[age] => The number must be greater than 0."
$user = User::from([
    'name' => 'John',
    'age' => -42,
    'email' => 'john@example.com',
    'sex' => 'male',
    'emails' => 'true',
]);
