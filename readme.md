# Pup

<p align="center">
  <a href="https://github.com/try-again-later/pup/actions/workflows/Tests.yml">
    <img
      src="https://github.com/try-again-later/pup/actions/workflows/Tests.yml/badge.svg"
      alt="Tests"
    >
  </a>
  <a href="https://packagist.org/packages/try-again-later/pup">
    <img
      src="https://img.shields.io/packagist/v/try-again-later/pup"
      alt="Latest Version"
    >
  </a>
  <a href="https://packagist.org/packages/try-again-later/pup">
    <img
      src="https://img.shields.io/packagist/l/try-again-later/pup"
      alt="Latest Version"
    >
  </a>
</p>

A small PHP library for value parsing and validation inspired by
[yup](https://github.com/jquense/yup).

## Examples

### Create schema using builder syntax

```php
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
    'age' => 42,
    'email' => 'john@example.com',
    'sex' => 'male',
    'allowSendingEmails' => 'true',
];

// validatedUser: [
//   name               => 'NAME = John',
//   age                => -42,
//   email              => 'john@example.com',
//   website            => 'No website',
//   sex                => 'male',
//   allowSendingEmails => bool(true)
// ]

// errors: [
//   ['age', 'The number must be greater than 0']
// ]
[$validatedUser, $errors] = $userSchema->validate($user)->tryGet();
```

### Create schema by annotating existing class

```php
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

// Throws InvalidArgumentException with the following message:
// "[age] => The number must be greater than 0."
$user = User::from([
    'name' => 'John',
    'age' => -42,
    'email' => 'john@example.com',
    'sex' => 'male',
    'emails' => 'true',
]);
```
