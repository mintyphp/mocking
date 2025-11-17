<?php

namespace MintyPHP\Mocking;

use Exception;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Throwable;

class StaticMethodMock
{
    // Static properties
    /** @var ?callable */
    private static $autoloader = null;
    /** @var array<string,StaticMethodMock> */
    public static array $mocks = [];

    // Instance properties
    /** @var string */
    private string $className;
    /** @var TestCase */
    private TestCase $testCase;
    /** @var array<int,array{method:string,arguments:array<int,mixed>,returns:mixed,exception:?Throwable}> $expectations*/
    private array $expectations;

    /** Register a static mock for the given class name.
     * @param string $className The class name to mock
     * @param TestCase $testCase The PHPUnit test case
     */
    public function __construct(string $className, TestCase $testCase)
    {
        $this->className = $className;
        $this->testCase = $testCase;
        $this->expectations = [];
        self::$mocks[$this->className] = $this;
        if (self::$autoloader === null) {
            self::$autoloader = static function (string $class): void {
                if (in_array($class, array_keys(self::$mocks))) {
                    $namespace = substr($class, 0, strrpos($class, '\\') + 0);
                    $shortClassName = substr($class, strrpos($class, '\\') + 1);
                    eval("namespace $namespace { class $shortClassName { public static function __callStatic(\$name, \$arguments) { return \\MintyPHP\\Mocking\\StaticMethodMock::handleStaticCall('$class', \$name, \$arguments); } } }");
                }
            };
            spl_autoload_register(self::$autoloader, true, true);
        }
    }

    /** Expect a with specific body (exact match). 
     * @param string $method The static method name
     * @param array<int,mixed> $arguments The arguments to expect
     * @param mixed $returns The return value if not void
     * @param ?Throwable $exception An optional exception to throw
     */
    public function expect(string $method, array $arguments, mixed $returns = null, ?Throwable $exception = null): void
    {
        $this->expectations[] = [
            'method' => strtoupper($method),
            'arguments' => $arguments,
            'returns' => $returns,
            'exception' => $exception,
        ];
    }

    /** Assert that all expectations were met. */
    public function assertExpectationsMet(): void
    {
        if (!empty($this->expectations)) {
            $this->testCase->fail(sprintf('Not all expectations met for %s, %d remaining', $this->className, count($this->expectations)));
        }
    }

    /**
     * Handle a static call to a mocked class.
     * @param string $className The class name
     * @param string $method The method name
     * @param array<int,mixed> $arguments The method arguments
     * @return mixed The return value
     * @throws Exception If no mock is registered or expectation fails
     * @throws ExpectationFailedException If expectation fails
     */
    public static function handleStaticCall(string $className, string $method, array $arguments): mixed
    {
        if (!isset(self::$mocks[$className])) {
            throw new Exception(sprintf('No mock registered for class: %s', $className));
        }
        $mock = self::$mocks[$className];
        if (empty($mock->expectations)) {
            $mock->testCase->fail(sprintf('No expectations left for %s::%s', $className, $method));
        }
        $expected = array_shift($mock->expectations);
        $mock->testCase->assertEquals($expected['method'], strtoupper($method), sprintf('Method mismatch for %s::%s', $className, $method));
        $mock->testCase->assertEquals($expected['arguments'], $arguments, sprintf('Arguments mismatch for %s::%s', $className, $method));
        if ($expected['exception'] !== null) {
            throw $expected['exception'];
        }
        return $expected['returns'];
    }
}
