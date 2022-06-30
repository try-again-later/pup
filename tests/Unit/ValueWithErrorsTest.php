<?php

declare(strict_types = 1);

use PHPUnit\Framework\TestCase;

use TryAgainLater\Pup\Util\ValueWithErrors;

class ValueWithErrorsTest extends TestCase
{
    // ::makeValue()

    public function testMakeValue_createsValue()
    {
        $value = 'some value';
        $withErrors = ValueWithErrors::makeValue($value);

        $actualHasValue = $withErrors->hasValue();
        $actualValue = $withErrors->value();

        $this->assertTrue($actualHasValue);
        $this->assertEquals($value, $actualValue);
    }

    public function testMakeValue_doesNotCreateErrors()
    {
        $withErrors = ValueWithErrors::makeValue('some value');

        $actualHasErrors = $withErrors->hasErrors();
        $actualErrors = $withErrors->errors();

        $this->assertFalse($actualHasErrors);
        $this->assertCount(0, $actualErrors);
    }

    // ::makeError()

    public function testMakeError_createsSingleError()
    {
        $error = 'some error';
        $withErrors = ValueWithErrors::makeError($error);

        $actualHasErrors = $withErrors->hasErrors();
        $actualErrors = $withErrors->errors();

        $this->assertTrue($actualHasErrors);
        $this->assertCount(1, $actualErrors);
        $this->assertContains($error, $actualErrors);
    }

    public function testMakeError_doesNotCreateValue()
    {
        $withErrors = ValueWithErrors::makeError('some error');

        $actualHasValue = $withErrors->hasValue();

        $this->assertFalse($actualHasValue);
        $this->expectException(LogicException::class);
        $withErrors->value();
    }

    // ::makeNothing()

    public function testMakeNothing_doesNotCreateValue()
    {
        $withErrors = ValueWithErrors::makeNothing();

        $actualHasValue = $withErrors->hasValue();

        $this->assertFalse($actualHasValue);
        $this->expectException(LogicException::class);
        $withErrors->value();
    }

    public function testMakeNothing_doesNotCreateError()
    {
        $withErrors = ValueWithErrors::makeNothing();

        $actualHasErrors = $withErrors->hasErrors();
        $actualErrors = $withErrors->errors();

        $this->assertFalse($actualHasErrors);
        $this->assertCount(0, $actualErrors);
    }

    // ->pushErrors()

    public function testPushErrors_createsErrors_whenThereAreNoneInitially()
    {
        $errors = ['first error', 'second error'];
        $withErrors = ValueWithErrors::makeNothing();

        $afterPushErrors = $withErrors->pushErrors(...$errors);
        $actualErrors = $afterPushErrors->errors();
        $actualHasErrors = $afterPushErrors->hasErrors();

        $this->assertTrue($actualHasErrors);
        $this->assertEqualsCanonicalizing($errors, $actualErrors);
    }

    public function testPushErrors_appendsErrors_whenThereAreAlreadySome()
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

    public function testPushErrorsIfValue_doesNothing_whenThePredicateIsFalse()
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

    public function testPushErrorsIfValue_appendsErrors_whenThePredicateIsTrue()
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

    public function testPushErrorsIfValue_throwsLogicException_whenThereIsNoValue()
    {
        $withErrors = ValueWithErrors::makeNothing();

        $this->expectException(LogicException::class);
        $withErrors->pushErrorsIfValue(
            fn () => 'mapped',
            'error',
        );
    }

    public function testPushErrorsIfValue_appendsErrorFromCallable_whenThePredicateIsTrue()
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

    public function testDropErrors_removesErrors()
    {
        $withErrors = ValueWithErrors::makeError('some error');

        $afterDroppedErrors = $withErrors->dropErrors();
        $actualHasErrors = $afterDroppedErrors->hasErrors();

        $this->assertFalse($actualHasErrors);
    }

    // ->mapValue

    public function testMapValue_mapsValue_whenSignleMappingProvided()
    {
        $initialValue = 42;
        $mapping = fn ($x) => 2 * $x;
        $valueAfterMapping = $mapping($initialValue);
        $withErrors = ValueWithErrors::makeValue($initialValue);

        $afterMapValue = $withErrors->mapValue($mapping);
        $actualValueAfterMapping = $afterMapValue->value();

        $this->assertEquals($valueAfterMapping, $actualValueAfterMapping);
    }

    public function testMapValue_mapsValue_whenMultipleMappingsProvided()
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

    public function testMapValue_throwsLogicException_whenThereIsNoValue()
    {
        $withErrors = ValueWithErrors::makeNothing();

        $this->expectException(LogicException::class);
        $withErrors->mapValue(fn () => 'mapped');
    }

    // ->next()

    public function testNext_appliesSingleCheck_whenCheckReturnsNewWrapper()
    {
        $initial = ValueWithErrors::makeNothing();
        $returnedFromCheck = ValueWithErrors::makeNothing();

        $returnedFromNext = $initial->next(fn () => $returnedFromCheck);

        $this->assertEquals($returnedFromCheck, $returnedFromNext);
    }

    public function testNext_returnsSameWrapper_whenCheckDoesNotReturnWrapper()
    {
        $initial = ValueWithErrors::makeNothing();

        $returnedFromNext = $initial->next(fn () => 'Not ValueWithErrors');

        $this->assertEquals($initial, $returnedFromNext);
    }

    public function testNext_appliesMultipleChecks()
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
