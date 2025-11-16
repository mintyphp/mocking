<?php

namespace MintyPHP\Mocking;

use Exception;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Throwable;

class StaticMethodMock
{
    /** @var ?callable */
    private static $autoloader = null;
    /** @var array<string,StaticMethodMock> */
    public static array $mocks = [];
    /** @var string */
    private string $className;
    /** @var TestCase */
    private TestCase $testCase;
    /** @var array<int,array{method:string,arguments:array<int,mixed>,returns:mixed,exception:?Throwable}> $expectations*/
    private array $expectations = [];

    // Register a static mock for the given class name.
    public function __construct(string $className, TestCase $testCase)
    {
        $this->className = $className;
        $this->testCase = $testCase;
        self::$mocks[$className] = $this;
        if (self::$autoloader === null) {
            self::$autoloader = function (string $class): void {
                if ($class === $this->className) {
                    $namespace = substr($this->className, 0, strrpos($this->className, '\\') + 0);
                    $shortClassName = substr($this->className, strrpos($this->className, '\\') + 1);
                    eval('namespace ' . $namespace . ' { class ' . $shortClassName . ' { public static function __callStatic($name, $arguments) { return \MintyPHP\Mocking\StaticMethodMock::handleStaticCall(\'' . $this->className . '\', $name, $arguments); } } }');
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
            throw new Exception(sprintf('StaticMethodMock no mock registered for class: %s', $className));
        }
        $mock = self::$mocks[$className];
        if (empty($mock->expectations)) {
            $mock->testCase->fail(sprintf('StaticMethodMock no expectations left for %s::%s', $className, $method));
        }
        $expected = array_shift($mock->expectations);
        $mock->testCase->assertEquals($expected['method'], strtoupper($method), sprintf('StaticMethodMock method mismatch for %s::%s', $className, $method));
        $mock->testCase->assertEquals($expected['arguments'], $arguments, sprintf('StaticMethodMock arguments mismatch for %s::%s', $className, $method));
        if ($expected['exception'] !== null) {
            throw $expected['exception'];
        }
        return $expected['returns'];
    }
}
