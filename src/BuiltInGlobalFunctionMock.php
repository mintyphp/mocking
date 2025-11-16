<?php

namespace MintyPHP\Mocking;

use Exception;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Throwable;

class BuiltInFunctionMock
{
    // Static properties
    /** @var array<string,BuiltInFunctionMock> */
    public static array $mocks = [];

    // Instance properties
    /** @var string */
    private string $affectedNamespace;
    /** @var TestCase */
    private TestCase $testCase;
    /** @var array<int,array{function:string,arguments:array<int,mixed>,returns:mixed,exception:?Throwable}> $expectations*/
    private array $expectations;

    /** 
     * Register a static mock for the given class name.
     * @param string $affectedNamespace The namespace where the global function is used and should be mocked
     * @param TestCase $testCase The PHPUnit test case
     */
    public function __construct(string $affectedNamespace, TestCase $testCase)
    {
        $this->affectedNamespace = $affectedNamespace;
        $this->testCase = $testCase;
        $this->expectations = [];
        self::$mocks[$this->affectedNamespace] = $this;
    }

    /** 
     * Expect a with specific body (exact match). 
     * @param string $function The function name
     * @param array<int,mixed> $arguments The arguments to expect
     * @param mixed $returns The return value if not void
     * @param ?Throwable $exception An optional exception to throw
     */
    public function expect(string $function, array $arguments, mixed $returns = null, ?Throwable $exception = null): void
    {
        $namespace = $this->affectedNamespace;
        if (!function_exists("$namespace\\$function")) {
            eval("namespace $namespace { function $function() { return \\MintyPHP\\Mocking\\BuiltInFunctionMock::handleFunctionCall('$namespace','$function',func_get_args()); } }");
        }
        $this->expectations[] = [
            'function' => $function,
            'arguments' => $arguments,
            'returns' => $returns,
            'exception' => $exception,
        ];
    }

    /** Assert that all expectations were met. */
    public function assertExpectationsMet(): void
    {
        if (!empty($this->expectations)) {
            $this->testCase->fail(sprintf('Not all expectations met for %s, %d remaining', $this->affectedNamespace, count($this->expectations)));
        }
    }

    /**
     * Handle a static call to a mocked class.
     * @param string $namespace The namespace the function is called from
     * @param string $function The global function name that is called
     * @param array<int,mixed> $arguments The arguments passed to the function
     * @return mixed The return value
     * @throws Exception If no mock is registered or expectation fails
     * @throws ExpectationFailedException If expectation fails
     */
    public static function handleFunctionCall(string $namespace, string $function, array $arguments): mixed
    {
        if (!isset(self::$mocks[$namespace])) {
            throw new Exception(sprintf('No mock registered for function: %s', $namespace));
        }
        $mock = self::$mocks[$namespace];
        if (empty($mock->expectations)) {
            $mock->testCase->fail(sprintf('No expectations left for %s', $function));
        }
        $expected = array_shift($mock->expectations);
        $mock->testCase->assertEquals($expected['function'], $function, 'Unexpected function called');
        $mock->testCase->assertEquals($expected['arguments'], $arguments, sprintf('Arguments mismatch for %s', $function));
        if ($expected['exception'] !== null) {
            throw $expected['exception'];
        }
        return $expected['returns'];
    }
}
