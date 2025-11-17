<?php

namespace MintyPHP\Mocking\Tests;

use MintyPHP\Mocking\StaticMethodMock;
use MintyPHP\Mocking\Tests\Math\Adder;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

class StaticMethodMockTest extends TestCase
{
    public function testAdderAdd(): void
    {
        // Create a static method mock for the Adder class
        $mock = new StaticMethodMock(Adder::class, $this);
        // Set expectation for the add method
        $mock->expect('add', [1, 2], 3);
        // Call the public static add method
        $result = Adder::add(1, 2);
        // Verify the result
        $this->assertEquals(3, $result);
        // Assert that all expectations were met
        $mock->assertExpectationsMet();
    }

    public function testExtraExpectations(): void
    {
        // Create a static method mock for the Adder class
        $mock = new StaticMethodMock(Adder::class, $this);
        // Set expectation for the add method
        $mock->expect('add', [1, 2], 3);
        $mock->expect('add', [1, 2], 3);
        // Call the public static add method
        $result = Adder::add(1, 2);
        // Verify the result
        $this->assertEquals(3, $result);
        // Assert that all expectations were met (expected to fail)
        try {
            $mock->assertExpectationsMet();
            $this->fail('Expected AssertionFailedError was not thrown.');
        } catch (AssertionFailedError $e) {
            $this->assertEquals('Not all expectations met for MintyPHP\Mocking\Tests\Math\Adder, 1 remaining', $e->getMessage());
        }
    }

    public function testNotEnoughExpectations(): void
    {
        // Create a static method mock for the Adder class
        $mock = new StaticMethodMock(Adder::class, $this);
        // Set expectation for the add method
        $mock->expect('add', [1, 2], 3);
        // Call the public static add method
        Adder::add(1, 2);
        try {
            // Call the public static add method again without expectation
            Adder::add(1, 2);
            $this->fail('Expected AssertionFailedError was not thrown.');
        } catch (AssertionFailedError $e) {
            $this->assertEquals('No expectations left for MintyPHP\Mocking\Tests\Math\Adder::add', $e->getMessage());
        }
    }

    public function testImaginaryAdderAdd(): void
    {
        // Create a static method mock for the Adder class
        $mock = new StaticMethodMock('Imaginary\Adder', $this);
        // Set expectation for the add method
        $mock->expect('add', [1, 2], 3);
        // Call the public static add method
        $result = forward_static_call('Imaginary\Adder::add', 1, 2);
        // Verify the result
        $this->assertEquals(3, $result);
        // Assert that all expectations were met
        $mock->assertExpectationsMet();
    }
}
