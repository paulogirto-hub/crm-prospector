<?php
/**
 * Direct migration runner - creates all CRM tables
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/app/Core/Bootstrap.php';

try {
    $pdo = Bootstrap::db();
    echo "Connected to DB\n";
    
    // Run migrations directory
    $migrationsPath = __DIR__ . '/migrations';
    $files = glob($migrationsPath . '/[0-9]*.sql');
    sort($files);
    
    echo "Found " . count($files) . " migration files\n";
    
    foreach ($files as $file) {
        $name = basename($file);
        echo "Running: $name... ";
        
        // Check if already run
        $stmt = $pdo->query("SELECT COUNT(*) FROM schema_migrations WHERE version = '$name'");
        if ($stmt->fetchColumn() > 0) {
            echo "SKIPPED (already run)\n";
            continue;
        }
        
        $sql = file_get_contents($file);
        $pdo->exec($sql);
        
        // Record migration
        $pdo->exec("INSERT INTO schema_migrations (version, applied_at) VALUES ('$name', NOW())");
        echo "OK\n";
    }
    
    echo "\nAll migrations complete!\n";
    echo "Tables: ";
    $tables = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'")->fetchAll(PDO::FETCH_COLUMN);
    echo implode(', ', $tables) . "\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}