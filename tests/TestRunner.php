<?php
/**
 * Test Runner — ejecuta todos los tests de la suite.
 * Uso: php tests/TestRunner.php [--filter=NombreTest]
 *
 * Convenciones:
 *   - Cada método cuyo nombre empiece por "test" es un caso de prueba.
 *   - $this->assert*(...)  para verificar condiciones.
 *   - $this->skip($msg)    para saltar un test (requiere BD live, etc.).
 */

define('TEST_ROOT', __DIR__);
define('APP_ROOT',  dirname(__DIR__));

// Autoload básico
spl_autoload_register(function (string $class): void {
    $paths = [
        APP_ROOT . '/tests/unit/'        . $class . '.php',
        APP_ROOT . '/tests/integration/' . $class . '.php',
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) { require_once $path; return; }
    }
});

// ─── Framework mínimo ────────────────────────────────────────────────────────

class TestCase {
    protected array $assertions = [];
    protected array $failures   = [];
    protected array $skipped    = [];
    private   string $current   = '';

    final public function runAll(string $filter = ''): array {
        $methods = array_filter(
            get_class_methods($this),
            fn($m) => str_starts_with($m, 'test') &&
                      ($filter === '' || str_contains(strtolower($m), strtolower($filter)))
        );

        foreach ($methods as $method) {
            $this->current = $method;
            try {
                $this->setUp();
                $this->$method();
                $this->tearDown();
            } catch (SkipException $e) {
                $this->skipped[] = [$method, $e->getMessage()];
            } catch (AssertionError $e) {
                $this->failures[] = [$method, $e->getMessage()];
            } catch (Throwable $e) {
                $this->failures[] = [$method, get_class($e) . ': ' . $e->getMessage()];
            }
        }

        return [
            'class'      => static::class,
            'assertions' => $this->assertions,
            'failures'   => $this->failures,
            'skipped'    => $this->skipped,
        ];
    }

    protected function setUp(): void {}
    protected function tearDown(): void {}

    protected function skip(string $reason = ''): void {
        throw new SkipException($reason);
    }

    // ── Assertions ──────────────────────────────────────────────────────────

    protected function assertTrue($val, string $msg = ''): void {
        $this->record(!!$val, $msg ?: "Expected true, got " . var_export($val, true));
    }

    protected function assertFalse($val, string $msg = ''): void {
        $this->record(!$val, $msg ?: "Expected false, got " . var_export($val, true));
    }

    protected function assertEquals($expected, $actual, string $msg = ''): void {
        $this->record(
            $expected === $actual,
            $msg ?: "Expected " . var_export($expected, true) . ", got " . var_export($actual, true)
        );
    }

    protected function assertNotEmpty($val, string $msg = 'Expected non-empty value'): void {
        $this->record(!empty($val), $msg);
    }

    protected function assertIsArray($val, string $msg = 'Expected array'): void {
        $this->record(is_array($val), $msg);
    }

    protected function assertArrayHasKey(string $key, array $arr, string $msg = ''): void {
        $this->record(array_key_exists($key, $arr), $msg ?: "Key '{$key}' not found in array");
    }

    protected function assertGreaterThan($min, $actual, string $msg = ''): void {
        $this->record($actual > $min, $msg ?: "{$actual} is not > {$min}");
    }

    protected function assertGreaterThanOrEqual($min, $actual, string $msg = ''): void {
        $this->record($actual >= $min, $msg ?: "{$actual} is not >= {$min}");
    }

    protected function assertNull($val, string $msg = 'Expected null'): void {
        $this->record(is_null($val), $msg);
    }

    protected function assertNotNull($val, string $msg = 'Expected non-null value'): void {
        $this->record(!is_null($val), $msg);
    }

    protected function assertStringContains(string $needle, string $haystack, string $msg = ''): void {
        $this->record(
            str_contains($haystack, $needle),
            $msg ?: "'{$needle}' not found in string"
        );
    }

    protected function assertCount(int $expected, array $arr, string $msg = ''): void {
        $actual = count($arr);
        $this->record($actual === $expected, $msg ?: "Expected count {$expected}, got {$actual}");
    }

    private function record(bool $ok, string $message): void {
        $this->assertions[] = ['test' => $this->current, 'ok' => $ok, 'msg' => $message];
        if (!$ok) throw new AssertionError($message);
    }
}

class SkipException extends RuntimeException {}

// ─── Carga de tests ──────────────────────────────────────────────────────────

$filter = '';
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--filter=')) {
        $filter = substr($arg, 9);
    }
}

$testFiles = array_merge(
    glob(TEST_ROOT . '/unit/*.php'),
    glob(TEST_ROOT . '/integration/*.php')
);

$results   = [];
$totPass   = 0;
$totFail   = 0;
$totSkip   = 0;

foreach ($testFiles as $file) {
    require_once $file;
    $class = basename($file, '.php');
    if (!class_exists($class) || !is_subclass_of($class, TestCase::class)) continue;

    $instance = new $class();
    $result   = $instance->runAll($filter);
    $results[] = $result;

    $pass = count($result['assertions']) - count($result['failures']);
    $fail = count($result['failures']);
    $skip = count($result['skipped']);
    $totPass += $pass;
    $totFail += $fail;
    $totSkip += $skip;

    $statusIcon = $fail > 0 ? '✗' : '✓';
    $statusColor = $fail > 0 ? "\033[31m" : "\033[32m";
    echo "{$statusColor}{$statusIcon}\033[0m  {$result['class']}  ";
    echo "({$pass} ok, {$fail} fail, {$skip} skip)\n";

    foreach ($result['failures'] as [$method, $msg]) {
        echo "   \033[31m↳ FAIL\033[0m  {$method}: {$msg}\n";
    }
    foreach ($result['skipped'] as [$method, $msg]) {
        echo "   \033[33m↳ SKIP\033[0m  {$method}: {$msg}\n";
    }
}

echo "\n────────────────────────────────────────\n";
$totalColor = $totFail > 0 ? "\033[31m" : "\033[32m";
echo "{$totalColor}Resultado: {$totPass} passed, {$totFail} failed, {$totSkip} skipped\033[0m\n";

exit($totFail > 0 ? 1 : 0);
