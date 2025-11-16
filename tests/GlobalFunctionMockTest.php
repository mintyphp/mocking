<?php

namespace MintyPHP\Mocking\Tests;

use MintyPHP\Mocking\GlobalFunctionMock;
use MintyPHP\Mocking\Tests\Time\StopWatch;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

class GlobalFunctionMockTest extends TestCase
{
    public function testStopWatchStartStop(): void
    {
        // Create a static method mock for the Adder class
        $mock = new GlobalFunctionMock('MintyPHP\Mocking\Tests\Time', $this);
        // Set expectation for the microtime function
        $mock->expect('microtime',[true], 1763333612.602);
        $mock->expect('microtime',[true], 1763333614.825);
        // Use the StopWatch class which uses the global function
        $stopWatch = new StopWatch();
        $stopWatch->start();
        $result = $stopWatch->stop();
        // Verify the result
        $this->assertEquals(2223, $result);
        // Assert that all expectations were met
        $mock->assertExpectationsMet();
    }

    public function testExtraExpectations(): void
    {
        // Create a static method mock for the Adder class
        $mock = new GlobalFunctionMock('MintyPHP\Mocking\Tests\Time', $this);
        // Set expectation for the microtime function
        $mock->expect('microtime',[true], 1763333612.602);
        $mock->expect('microtime',[true], 1763333614.825);
        $mock->expect('microtime',[true], 1763333616.288);
        // Use the StopWatch class which uses the global function
        $stopWatch = new StopWatch();
        $stopWatch->start();
        $stopWatch->stop();
        // Assert that all expectations were met
        try {
            $mock->assertExpectationsMet();
            $this->fail('Expected AssertionFailedError was not thrown.');
        } catch (AssertionFailedError $e) {
            $this->assertEquals('Not all expectations met for MintyPHP\Mocking\Tests\Time, 1 remaining', $e->getMessage());
        }   
    }

    public function testNotEnoughExpectations(): void
    {
        // Create a static method mock for the Adder class
        $mock = new GlobalFunctionMock('MintyPHP\Mocking\Tests\Time', $this);
        // Set expectation for the microtime function
        $mock->expect('microtime',[true], 1763333612.602);
        // Use the StopWatch class which uses the global function
        $stopWatch = new StopWatch();
        $stopWatch->start();
        try {
            // Call stop without expectation
            $stopWatch->stop();
            $this->fail('Expected AssertionFailedError was not thrown.');
        } catch (AssertionFailedError $e) {
            $this->assertEquals('No expectations left for microtime', $e->getMessage());
        }
    }
}
