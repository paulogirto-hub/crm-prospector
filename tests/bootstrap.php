<?php
/**
 * PHPUnit Bootstrap — Prospec CRM
 * 
 * Carrega dependências, conecta DB, inicializa serviços.
 * Rodado antes de cada teste (via phpunit.xml bootstrap).
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Core/Helper.php';
require_once __DIR__ . '/../app/Core/functions.php';

use App\Core\Config;
use App\Core\Session;
use App\Core\Auth;
use App\Core\Validator;
use App\Core\AuditLog;
use App\Core\Model;
use App\Core\RateLimit;
use App\Core\Flash;

// 1. Load config
Config::load(__DIR__ . '/../.env');

// 2. PDO connection
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
    echo "❌ FATAL: Cannot connect to DB: " . $e->getMessage() . "\n";
    exit(1);
}

// 3. Init services
Model::setPdo($pdo);
Auth::setPdo($pdo);
Validator::setPdo($pdo);
AuditLog::setPdo($pdo);

// 4. Make $pdo globally available for tests
$GLOBALS['pdo'] = $pdo;