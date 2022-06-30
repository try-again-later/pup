# Pup

A small PHP library for value parsing and validation inspired by
[yup](https://github.com/jquense/yup).

## Example

```php
use TryAgainLater\Pup\Schema;

$userSchema = Schema::associativeArray([
    'name' => Schema::string()->required()->transform(fn ($name) => "NAME = $name"),
    'age' => Schema::int()->positive(),
    'email' => Schema::string()->required()->max(255),
    'website' => Schema::string()->default('No website'),
]);

$user = [
    'name' => 'John',
    'age' => -42,
    'email' => 'john@example.com',
];

// validatedUser: [
//   name    => 'NAME = John',
//   age     => -42,
//   email   => 'john@example.com',
//   website => 'No website',
// ]

// errors: [
//   ['age', 'The number must be greater than 0']
// ]
[$validatedUser, $errors] = $userSchema->validate($user)->get();
```
