<?php

declare(strict_types = 1);

use PHPUnit\Framework\TestCase;

use TryAgainLater\Pup\Util\ValueWithErrors;

// TODO: Cover every method with tests
class ValueWithErrorsTest extends TestCase
{
    public function test_HasValueReturnsTrue_WhenCreatedWithMakeValue()
    {
        $withErrors = ValueWithErrors::makeValue('some value');

        $this->assertTrue($withErrors->hasValue());
    }

    public function test_HasValueReturnsFalse_WhenCreatedWithMakeError()
    {
        $withErrors = ValueWithErrors::makeError('some error');

        $this->assertFalse($withErrors->hasValue());
    }

    public function test_ValueReturnsCorrectValue_WhenCreatedWithMakeValue()
    {
        $value = 'some value';

        $withErrors = ValueWithErrors::makeValue($value);

        $this->assertEquals($value, $withErrors->value());
    }

    public function test_ValueThrowsLogicException_WhenCreatedWithMakeError()
    {
        $withErrors = ValueWithErrors::makeError('some error');

        $this->expectException(LogicException::class);
        $value = $withErrors->value();
    }
}
