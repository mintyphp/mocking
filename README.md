# MintyPHP Mocking

Lightweight utilities to mock static methods and built-in functions in PHP unit tests without invasive refactors.

This library works by:
- Intercepting class autoload for specific classes to provide a stub that forwards static calls to your expectations
- Defining namespaced functions on the fly so unqualified function calls (like `microtime()`) in that namespace are routed through your mock

It’s intentionally small, explicit, and easy to reason about.

## Requirements

- PHP 7.4+
- PHPUnit (dev dependency)

## Installation

Add as a dev dependency with Composer:

```bash
composer require --dev mintyphp/mocking
```

Ensure Composer’s autoloader is required in your test bootstrap (PHPUnit usually does this for you).

## Mocking static methods

Contract:
- You register a mock for a fully-qualified class name before that class is loaded the first time in the process
- You declare one or more ordered expectations: method name, exact argument list, optional return value or exception
- Each static call consumes the next expectation and either returns the value or throws the exception
- Any leftover expectations cause a test failure when you assert

Example (taken from `tests/StaticMethodMockTest.php`):

```php
use MintyPHP\Mocking\StaticMethodMock;
use MintyPHP\Mocking\Tests\Math\Adder; // Class with a static method we want to mock
use PHPUnit\Framework\TestCase;

class MyTest extends TestCase
{
    public function test_add(): void
    {
        // 1) Register the mock (before Adder is ever loaded)
        $mock = new StaticMethodMock(Adder::class, $this);

        // 2) Declare expectations in order
        $mock->expect('add', [1, 2], 3);

        // 3) Exercise the code under test
        $result = Adder::add(1, 2);

        // 4) Assert and verify expectations were fully consumed
        $this->assertSame(3, $result);
        $mock->assertExpectationsMet();
    }
}
```

Notes and caveats:
- Ordering matters: expectations are matched and consumed in FIFO order
- Method name is compared case-insensitively; arguments use PHPUnit’s assertEquals comparison
- If you call more times than you declared, the next call fails with "No expectations left …"
- If you declared more expectations than were consumed, `assertExpectationsMet()` fails and lists how many remain
- The mock wins the autoload race by registering a prepended autoloader; it only works if the real class has not already been defined earlier in the process

Throwing instead of returning:

```php
$mock->expect('danger', ['x'], null, new \RuntimeException('boom'));
// Calling Adder::danger('x') will throw that exception
```

## Mocking built-in functions

Contract:
- You register a mock for the namespace where the function is called from
- You declare one or more ordered expectations: function name, exact argument list, optional return value or exception
- The library defines a function in that namespace (once) that forwards calls to your expectations

Example (taken from `tests/BuiltInFunctionMockTest.php`):

```php
use MintyPHP\Mocking\BuiltInFunctionMock;
use MintyPHP\Mocking\Tests\Time\StopWatch; // Calls microtime() inside its own namespace
use PHPUnit\Framework\TestCase;

class MyTest extends TestCase
{
    public function test_stopwatch(): void
    {
        // 1) Register the mock for the namespace where StopWatch lives
        $mock = new BuiltInFunctionMock('MintyPHP\\Mocking\\Tests\\Time', $this);

        // 2) microtime(true) will be called twice; set exact expectations
        $mock->expect('microtime', [true], 1763333612.602);
        $mock->expect('microtime', [true], 1763333614.825);

        // 3) Exercise the code under test
        $sw = new StopWatch();
        $sw->start();
        $elapsedMs = $sw->stop();

        $this->assertSame(2223, $elapsedMs);
        $mock->assertExpectationsMet();
    }
}
```

Notes and caveats:
- This only intercepts unqualified calls within the targeted namespace, e.g. `microtime()` inside `MintyPHP\Mocking\Tests\Time`
- Fully-qualified calls like `\microtime()` or calls imported with `use function` that point to another namespace will NOT be intercepted
- Ordering and argument comparison rules are the same as for static method mocks

Throwing instead of returning:

```php
$mock = new BuiltInFunctionMock('App\\Service', $this);
$mock->expect('file_get_contents', ['https://example.com'], null, new \RuntimeException('network error'));
```

## Good to know

- Keep your mock registration close to the start of your test so it runs before the real class is autoloaded or the function is first called
- Expectations are per-mock-instance; each instance tracks and asserts its own queue
- Internals use `eval()` to define tiny proxy classes/functions during tests; this library is intended for test environments only

## Run the test suite

From the project root:

```bash
./vendor/bin/phpunit
```

## Alternatives

The following libraries are recommended to explore as (better) alternatives:

- [php-mock/php-mock](https://www.github.com/php-mock/php-mock)
- [mockery/mockery](https://www.github.com/mockery/mockery)
- [php-mock/php-mock-phpunit](https://www.github.com/php-mock/php-mock-phpunit)
