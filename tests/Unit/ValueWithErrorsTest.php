<?php

declare(strict_types = 1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

use TryAgainLater\Pup\Util\ValueWithErrors;

class ValueWithErrorsTest extends TestCase
{
    // ::makeValue()

    #[Test]
    public function makeValue_createsValue(): void
    {
        $value = 'some value';
        $withErrors = ValueWithErrors::makeValue($value);

        $actualHasValue = $withErrors->hasValue();
        $actualValue = $withErrors->value();

        $this->assertTrue($actualHasValue);
        $this->assertEquals($value, $actualValue);
    }

    #[Test]
    public function makeValue_doesNotCreateErrors(): void
    {
        $withErrors = ValueWithErrors::makeValue('some value');

        $actualHasErrors = $withErrors->hasErrors();
        $actualErrors = $withErrors->errors();

        $this->assertFalse($actualHasErrors);
        $this->assertCount(0, $actualErrors);
    }

    // ::makeError()

    #[Test]
    public function makeError_createsSingleError(): void
    {
        $error = 'some error';
        $withErrors = ValueWithErrors::makeError($error);

        $actualHasErrors = $withErrors->hasErrors();
        $actualErrors = $withErrors->errors();

        $this->assertTrue($actualHasErrors);
        $this->assertCount(1, $actualErrors);
        $this->assertContains($error, $actualErrors);
    }

    #[Test]
    public function makeError_doesNotCreateValue(): void
    {
        $withErrors = ValueWithErrors::makeError('some error');

        $actualHasValue = $withErrors->hasValue();

        $this->assertFalse($actualHasValue);
        $this->expectException(LogicException::class);
        $withErrors->value();
    }

    // ::makeNothing()

    #[Test]
    public function makeNothing_doesNotCreateValue(): void
    {
        $withErrors = ValueWithErrors::makeNothing();

        $actualHasValue = $withErrors->hasValue();

        $this->assertFalse($actualHasValue);
        $this->expectException(LogicException::class);
        $withErrors->value();
    }

    #[Test]
    public function makeNothing_doesNotCreateError(): void
    {
        $withErrors = ValueWithErrors::makeNothing();

        $actualHasErrors = $withErrors->hasErrors();
        $actualErrors = $withErrors->errors();

        $this->assertFalse($actualHasErrors);
        $this->assertCount(0, $actualErrors);
    }

    // ->pushErrors()

    #[Test]
    public function pushErrors_createsErrors_whenThereAreNoneInitially(): void
    {
        $errors = ['first error', 'second error'];
        $withErrors = ValueWithErrors::makeNothing();

        $afterPushErrors = $withErrors->pushErrors(...$errors);
        $actualErrors = $afterPushErrors->errors();
        $actualHasErrors = $afterPushErrors->hasErrors();

        $this->assertTrue($actualHasErrors);
        $this->assertEqualsCanonicalizing($errors, $actualErrors);
    }

    #[Test]
    public function pushErrors_appendsErrors_whenThereAreAlreadySome(): void
    {
        $initialErrors = ['first error'];
        $appendedErrors = ['second error', 'third error'];
        $withErrors = ValueWithErrors::makeError(...$initialErrors);

        $afterPushErrors = $withErrors->pushErrors(...$appendedErrors);
        $actualErrors = $afterPushErrors->errors();

        $this->assertEqualsCanonicalizing(
            [...$initialErrors, ...$appendedErrors],
            $actualErrors,
        );
    }

    // ->pushErrorsIfValue()

    #[Test]
    public function pushErrorsIfValue_doesNothing_whenThePredicateIsFalse(): void
    {
        $value = 42;
        $withErrors = ValueWithErrors::makeValue($value);

        $afterPushErrorsIfValue = $withErrors->pushErrorsIfValue(
            fn ($v) => $v !== $value,
            'first error',
            'second error',
        );

        $this->assertEquals($withErrors, $afterPushErrorsIfValue);
    }

    #[Test]
    public function pushErrorsIfValue_appendsErrors_whenThePredicateIsTrue(): void
    {
        $value = 42;
        $appendedErrors = ['first error', 'second error'];
        $withErrors = ValueWithErrors::makeValue($value);

        $afterPushErrorsIfValue = $withErrors->pushErrorsIfValue(
            fn ($v) => $v === $value,
            ...$appendedErrors,
        );
        $actualErrors = $afterPushErrorsIfValue->errors();

        $this->assertEqualsCanonicalizing($appendedErrors, $actualErrors);
    }

    #[Test]
    public function pushErrorsIfValue_throwsLogicException_whenThereIsNoValue(): void
    {
        $withErrors = ValueWithErrors::makeNothing();

        $this->expectException(LogicException::class);
        $withErrors->pushErrorsIfValue(
            fn () => 'mapped',
            'error',
        );
    }

    #[Test]
    public function pushErrorsIfValue_appendsErrorFromCallable_whenThePredicateIsTrue(): void
    {
        $value = 'some value';
        $errorGenerator = fn ($value) => "Error because of '$value'";
        $expectedErrors = [$errorGenerator($value)];
        $withErrors = ValueWithErrors::makeValue($value);

        $afterPushErrorsIfValue = $withErrors->pushErrorsIfValue(
            fn () => true,
            $errorGenerator,
        );
        $actualErrors = $afterPushErrorsIfValue->errors();

        $this->assertEqualsCanonicalizing($expectedErrors, $actualErrors);
    }

    // ->dropErrors()

    #[Test]
    public function dropErrors_removesErrors(): void
    {
        $withErrors = ValueWithErrors::makeError('some error');

        $afterDroppedErrors = $withErrors->dropErrors();
        $actualHasErrors = $afterDroppedErrors->hasErrors();

        $this->assertFalse($actualHasErrors);
    }

    // ->mapValue

    #[Test]
    public function mapValue_mapsValue_whenSignleMappingProvided(): void
    {
        $initialValue = 42;
        $mapping = fn ($x) => 2 * $x;
        $valueAfterMapping = $mapping($initialValue);
        $withErrors = ValueWithErrors::makeValue($initialValue);

        $afterMapValue = $withErrors->mapValue($mapping);
        $actualValueAfterMapping = $afterMapValue->value();

        $this->assertEquals($valueAfterMapping, $actualValueAfterMapping);
    }

    #[Test]
    public function mapValue_mapsValue_whenMultipleMappingsProvided(): void
    {
        $initialValue = 42;
        $firstMapping = fn ($x) => 2 * $x;
        $secondMapping = fn ($x) => 100 - $x;
        $valueAfterMapping = $secondMapping($firstMapping($initialValue));
        $withErrors = ValueWithErrors::makeValue($initialValue);

        $afterMapValue = $withErrors->mapValue($firstMapping, $secondMapping);
        $actualValueAfterMapping = $afterMapValue->value();

        $this->assertEquals($valueAfterMapping, $actualValueAfterMapping);
    }

    #[Test]
    public function mapValue_throwsLogicException_whenThereIsNoValue(): void
    {
        $withErrors = ValueWithErrors::makeNothing();

        $this->expectException(LogicException::class);
        $withErrors->mapValue(fn () => 'mapped');
    }

    // ->next()

    #[Test]
    public function next_appliesSingleCheck_whenCheckReturnsNewWrapper(): void
    {
        $initial = ValueWithErrors::makeNothing();
        $returnedFromCheck = ValueWithErrors::makeNothing();

        $returnedFromNext = $initial->next(fn () => $returnedFromCheck);

        $this->assertEquals($returnedFromCheck, $returnedFromNext);
    }

    #[Test]
    public function next_returnsSameWrapper_whenCheckDoesNotReturnWrapper(): void
    {
        $initial = ValueWithErrors::makeNothing();

        $returnedFromNext = $initial->next(fn () => 'Not ValueWithErrors');

        $this->assertEquals($initial, $returnedFromNext);
    }

    #[Test]
    public function next_appliesMultipleChecks(): void
    {
        $initial = ValueWithErrors::makeNothing();
        $afterFirstCheck = 'Not ValueWithErrors';
        $afterSecondCheck = ValueWithErrors::makeNothing();
        $afterThirdCheck = ValueWithErrors::makeNothing();

        $returnedFromNext = $initial->next(
            fn ($v) => $v === $initial ? $afterFirstCheck : 'Error',
            fn ($v) => $v === $afterFirstCheck ? $afterSecondCheck : 'Error',
            fn ($v) => $v === $afterSecondCheck ? $afterThirdCheck : 'Error',
            fn () => 'Not ValueWithErrors',
        );

        $this->assertEquals($afterThirdCheck, $returnedFromNext);
    }
}
