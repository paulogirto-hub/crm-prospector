<?php
/**
 * migrate.php — Script de migração com suporte a rollback
 * MELH-003: INFRA-18
 * 
 * Uso:
 *   php database/migrate.php          — Executa migrations pendentes
 *   php database/migrate.php rollback  — Faz rollback da última migration
 *   php database/migrate.php status    — Mostra status das migrations
 *   php database/migrate.php refresh   — Rollback + migrate (recria tudo)
 */

require_once __DIR__ . '/../app/Core/Config.php';
$configClass = 'App\Core\Config';
$configClass::load(__DIR__ . '/../.env');

use App\Core\Config;

$dsn = "pgsql:host=" . Config::get('DB_HOST', 'postgres') 
     . ";port=" . Config::get('DB_PORT', 5432) 
     . ";dbname=" . Config::get('DB_NAME', 'prospec_crm');

try {
    $pdo = new PDO($dsn, Config::get('DB_USER', 'prospec'), Config::get('DB_PASS', 'prospec123'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    echo "❌ Erro de conexão: " . $e->getMessage() . "\n";
    exit(1);
}

$command = $argv[1] ?? 'migrate';
$migrationsDir = __DIR__ . '/../migrations';
$downDir = __DIR__ . '/../migrations/down';

// ─── Commands ───

switch ($command) {
    case 'status':
        showStatus($pdo);
        break;
    case 'migrate':
        runMigrate($pdo, $migrationsDir);
        break;
    case 'rollback':
        runRollback($pdo, $downDir);
        break;
    case 'refresh':
        runRefresh($pdo, $downDir, $migrationsDir);
        break;
    default:
        echo "Comando desconhecido: {$command}\n";
        echo "Uso: php database/migrate.php [migrate|rollback|status|refresh]\n";
        exit(1);
}

// ─── Functions ───

function showStatus(PDO $pdo): void
{
    echo "📊 Status das Migrations\n";
    echo "═══════════════════════════════════════\n\n";

    $executed = $pdo->query("SELECT name, executed_at FROM _migrations ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    $executedNames = array_column($executed, 'name');

    $migrationFiles = glob(__DIR__ . '/../migrations/*.sql');
    sort($migrationFiles);

    foreach ($migrationFiles as $file) {
        $name = basename($file);
        $status = in_array($name, $executedNames) ? '✅' : '⏳';
        $date = '';
        foreach ($executed as $e) {
            if ($e['name'] === $name) {
                $date = $e['executed_at'];
                break;
            }
        }
        echo "  {$status} {$name}" . ($date ? " ({$date})" : ' (pendente)') . "\n";
    }

    echo "\n  Total: " . count($migrationFiles) . " migrations, " . count($executed) . " executadas\n";
}

function runMigrate(PDO $pdo, string $dir): void
{
    echo "🚀 Executando migrations...\n\n";

    $executed = $pdo->query("SELECT name FROM _migrations")->fetchAll(PDO::FETCH_COLUMN);
    $files = glob($dir . '/*.sql');
    sort($files);

    $count = 0;
    foreach ($files as $file) {
        $name = basename($file);
        if (in_array($name, $executed)) {
            continue;
        }

        echo "  ▶ {$name}...";

        try {
            $sql = file_get_contents($file);
            $pdo->exec($sql);

            $stmt = $pdo->prepare("INSERT INTO _migrations (name) VALUES (:name)");
            $stmt->execute(['name' => $name]);

            echo " ✅\n";
            $count++;
        } catch (Throwable $e) {
            echo " ❌ ERRO: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    if ($count === 0) {
        echo "  ⏭️  Nenhuma migration pendente\n";
    }

    echo "\n✨ {$count} migrations executadas\n";
}

function runRollback(PDO $pdo, string $downDir, int $steps = 1): void
{
    echo "⏪ Executando rollback ({$steps} step(s))...\n\n";

    $recent = $pdo->query("SELECT id, name FROM _migrations ORDER BY id DESC LIMIT {$steps}")->fetchAll(PDO::FETCH_ASSOC);

    if (empty($recent)) {
        echo "  ⏭️  Nenhuma migration para reverter\n";
        return;
    }

    foreach ($recent as $migration) {
        $name = $migration['name'];
        $id = $migration['id'];

        // Procurar arquivo down.sql
        $downFile = $downDir . '/' . $name;

        echo "  ▶ Rollback {$name}...";

        if (file_exists($downFile)) {
            try {
                $sql = file_get_contents($downFile);
                $pdo->exec($sql);
            } catch (Throwable $e) {
                echo " ❌ ERRO no down.sql: " . $e->getMessage() . "\n";
                exit(1);
            }
        } else {
            echo " ⚠️  Sem down.sql";
        }

        // Remover do tracking
        $stmt = $pdo->prepare("DELETE FROM _migrations WHERE id = :id");
        $stmt->execute(['id' => $id]);

        echo " ✅\n";
    }

    echo "\n⏪ Rollback concluído\n";
}

function runRefresh(PDO $pdo, string $downDir, string $migrationsDir): void
{
    echo "🔄 Refresh: rollback completo + migrate...\n\n";

    // Rollback ALL
    $all = $pdo->query("SELECT id, name FROM _migrations ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($all as $migration) {
        $downFile = $downDir . '/' . $migration['name'];
        if (file_exists($downFile)) {
            try {
                $sql = file_get_contents($downFile);
                $pdo->exec($sql);
            } catch (Throwable $e) {
                echo "  ⚠️  Erro no rollback de {$migration['name']}: " . $e->getMessage() . "\n";
            }
        }
        $stmt = $pdo->prepare("DELETE FROM _migrations WHERE id = :id");
        $stmt->execute(['id' => $migration['id']]);
    }

    echo "  ✅ Rollback completo\n\n";

    // Re-migrate
    runMigrate($pdo, $migrationsDir);
}