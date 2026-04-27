<?php
/**
 * Front Controller — Prospec CRM
 * 
 * Ponto de entrada de todas as requisições.
 * Bootstrap: Config → Logger → Session → PDO → Router → Dispatch
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../storage/logs/php-error.log');

// Autoload
require_once __DIR__ . '/../vendor/autoload.php';

// Global helpers (no namespace)
require_once __DIR__ . '/../app/Core/Helper.php';
require_once __DIR__ . '/../app/Core/helpers.php';

// Core classes
use App\Core\Config;
use App\Core\Logger;
use App\Core\Session;
use App\Core\Router;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Validator;
use App\Core\AuditLog;
use App\Core\Flash;
use App\Core\Model;

// 1. Carrega configuração
Config::load(__DIR__ . '/../.env');

// 2. Initialize Logger with request ID
$requestId = uniqid('req_', true);
Logger::setRequestId($requestId);
Logger::init($requestId);

// 3. Set custom error/exception handlers
set_error_handler([Logger::class, 'errorHandler']);
set_exception_handler([Logger::class, 'exceptionHandler']);

// 4. Log request start
$startTime = microtime(true);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = '/' . trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/', '/');
$path = preg_replace('#/+#', '/', $path);
$clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

Logger::info('Request started', [
    'method' => $method,
    'path'   => $path,
    'ip'     => $clientIp,
]);

// 5. Inicia sessão
Session::start();

// 6. Conexão PDO
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
    Logger::error('DB Connection Error: ' . $e->getMessage());
    
    // Se for requisição AJAX, retorna JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        http_response_code(503);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Banco de dados indisponível']);
        exit;
    }
    
    // Página de erro
    http_response_code(503);
    require __DIR__ . '/../app/Views/errors/500.php';
    exit;
}

// 7. Seta PDO nos models e serviços
Model::setPdo($pdo);
Auth::setPdo($pdo);
Validator::setPdo($pdo);
AuditLog::setPdo($pdo);
\App\Core\PasswordReset::setPdo($pdo);

// 8. Define rotas
require __DIR__ . '/../config/routes.php';

// 9. Dispatch
// Method spoofing via _method field
if ($method === 'POST' && isset($_POST['_method'])) {
    $method = strtoupper($_POST['_method']);
}

try {
    Router::dispatch($method, $path);
} catch (\Throwable $e) {
    Logger::error('Router Error: ' . $e->getMessage(), [
        'trace' => $e->getTraceAsString(),
    ]);
    
    if (Config::isDev()) {
        http_response_code(500);
        echo "<pre style='color:red'>" . htmlspecialchars($e->getMessage() . "\n\n" . $e->getTraceAsString()) . "</pre>";
    } else {
        http_response_code(500);
        require __DIR__ . '/../app/Views/errors/500.php';
    }
}

// 10. Log request completion
$duration = round((microtime(true) - $startTime) * 1000, 2);
$statusCode = http_response_code();

Logger::info('Request completed', [
    'status'   => $statusCode ?: 200,
    'duration' => $duration . 'ms',
]);