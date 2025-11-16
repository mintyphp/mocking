<?php

namespace MintyPHP\Mocking\Tests;

use MintyPHP\Mocking\StaticMethodMock;
use MintyPHP\Mocking\Tests\Math\Adder;
use PHPUnit\Framework\TestCase;

class StaticMethodMockTest extends TestCase
{
    public function testStaticMethodMock(): void
    {
        // Create a static method mock for the Adder class
        $mock = new StaticMethodMock(Adder::class, $this);
        // Set expectation for the add method
        $mock->expect('add', [1, 2], 3);
        // Call the public static add method
        $result = Adder::add(1, 2);
        // Verify the result
        $this->assertEquals(3, $result);
    }
}
