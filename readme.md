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

PHP библиотека для валидации с интерфейсом, вдохновлённым [yup](https://github.com/jquense/yup).
Позволяет парсить и создавать объекты из сырых массивов в соответствии с наобором правил, указанных
в атрибутах на полях класса.

---

PHP library for value parsing and validation with an interface inspired by
[yup](https://github.com/jquense/yup). Allows you to parse and create objects from raw arrays
according to the ruleset specified via attributes applied to the class fields.

## Example

```php
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

// Creates the object without any issues:
$user = User::from([
    'name' => 'John',
    'age' => 42,
]);

// Throws an exception because of negative age:
$user = User::from([
    'name' => 'John',
    'age' => -42,
]);

// Throws an exception because the name is too long:
$user = User::from([
    'name' => 'John Doe Blah Blah Blah',
    'age' => 42,
]);
```

More examples under the [examples folder](./examples/).

## Running tests and linter (on the library iteself)

```sh
composer test
composer lint
```
