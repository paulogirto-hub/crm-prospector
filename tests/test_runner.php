<?php
/**
 * Test Runner — Prospec CRM
 * 
 * Roda testes automatizados dentro do container PHP.
 * Uso: docker exec prospec-crm-php php /app/tests/test_runner.php
 */

// Bootstrap do app
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Core/Helper.php';
require_once __DIR__ . '/../app/Core/functions.php';

use App\Core\Config;
use App\Core\Session;
use App\Core\Auth;
use App\Core\Validator;
use App\Core\AuditLog;
use App\Core\Flash;
use App\Core\Model;
use App\Core\RateLimit;

// 1. Carrega config
Config::load(__DIR__ . '/../.env');

// 2. Conexão PDO
try {
    $pdo = new PDO(
        "pgsql:host=" . Config::get('DB_HOST', 'postgres') 
        . ";port=" . Config::get('DB_PORT', 5432) 
        . ";dbname=" . Config::get('DB_NAME', 'prospec_crm'),
        Config::get('DB_USER', 'prospec'),
        Config::get('DB_PASS', 'prospec123'),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    echo "❌ FATAL: Não conseguiu conectar ao banco: " . $e->getMessage() . "\n";
    exit(1);
}

Model::setPdo($pdo);
Auth::setPdo($pdo);
Validator::setPdo($pdo);
AuditLog::setPdo($pdo);

// 3. Carrega rotas
require __DIR__ . '/../config/routes.php';

// ─── Test Framework ───

/**
 * Define the abstract TestCase class BEFORE loading test files.
 * This is required because test files use `extends TestCase` at the top level,
 * and PHP requires the parent class to exist at parse time.
 */
abstract class TestCase
{
    protected \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    abstract public function runAll(): void;

    protected function assert(string $name, bool $condition, string $detail = ''): void
    {
        TestRunner::assert($name, $condition, $detail);
    }

    protected function assertEqual(string $name, mixed $expected, mixed $actual): void
    {
        $pass = $expected === $actual;
        $detail = $pass ? '' : "Expected: " . var_export($expected, true) . ", Got: " . var_export($actual, true);
        $this->assert($name, $pass, $detail);
    }

    protected function assertTrue(string $name, bool $value): void
    {
        $this->assert($name, $value === true);
    }

    protected function assertFalse(string $name, bool $value): void
    {
        $this->assert($name, $value === false);
    }

    protected function assertContains(string $name, string $needle, string $haystack): void
    {
        $this->assert($name, str_contains($haystack, $needle), "Expected '{$needle}' in string");
    }

    protected function assertNotContains(string $name, string $needle, string $haystack): void
    {
        $this->assert($name, !str_contains($haystack, $needle), "Did not expect '{$needle}' in string");
    }

    protected function assertNotEmpty(string $name, mixed $value): void
    {
        $this->assert($name, !empty($value));
    }

    protected function assertEmpty(string $name, mixed $value): void
    {
        $this->assert($name, empty($value));
    }

    protected function assertCount(string $name, int $expected, array $arr): void
    {
        $actual = count($arr);
        $this->assert($name, $actual === $expected, "Expected {$expected}, got {$actual}");
    }
}

/**
 * Test runner — collects and runs all test files.
 */
class TestRunner
{
    private static int $passed = 0;
    private static int $failed = 0;
    private static int $total = 0;

    public static function run(): void
    {
        echo "\n";
        echo "╔══════════════════════════════════════════╗\n";
        echo "║   PROSPEC CRM — Test Suite               ║\n";
        echo "╚══════════════════════════════════════════╝\n\n";

        $testFiles = glob(__DIR__ . '/Test*.php');
        sort($testFiles);

        foreach ($testFiles as $file) {
            // Load each test file — classes are in global namespace (no namespace)
            // or in Tests namespace, but TestCase is already defined above.
            // For Tests namespace classes, we need a trick.
            $content = file_get_contents($file);
            
            // Detect if file uses `namespace Tests;` 
            if (preg_match('/^namespace\s+Tests;/m', $content)) {
                // Wrap in namespace block to avoid re-declaring namespace
                $wrappedCode = "<?php\nnamespace Tests;\n" . substr($content, 5);
                // But we can't eval() a full file. Instead, load with eval using a temp file approach.
                // Actually, let's just load normally and PHP will handle class declarations.
                // The trick is: since TestCase is abstract and defined, PHP can resolve the inheritance.
                require_once $file;
            } else {
                require_once $file;
            }
            
            $className = basename($file, '.php');
            
            if (!class_exists($className)) continue;
            
            echo "📦 {$className}\n";
            echo str_repeat('─', 40) . "\n";
            
            $test = new $className($pdo);
            $test->runAll();
            
            echo "\n";
        }

        echo str_repeat('═', 44) . "\n";
        $total = self::$passed + self::$failed;
        echo "Resultados: {$total} testes | ";
        echo "✅ " . self::$passed . " passaram | ";
        echo "❌ " . self::$failed . " falharam\n";
        echo str_repeat('═', 44) . "\n\n";

        exit(self::$failed > 0 ? 1 : 0);
    }

    public static function assert(string $name, bool $result, string $detail = ''): void
    {
        self::$total++;
        if ($result) {
            self::$passed++;
            echo "  ✅ {$name}\n";
        } else {
            self::$failed++;
            echo "  ❌ {$name}" . ($detail ? " — {$detail}" : '') . "\n";
        }
    }
}

// Run!
TestRunner::run();