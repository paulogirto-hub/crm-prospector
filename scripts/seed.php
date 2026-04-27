<?php
/**
 * Seed.php — Dados iniciais do sistema
 * 
 * Cria usuário admin padrão: admin@prospec.com.br / Prospec@2026
 * 
 * Uso: php scripts/seed.php
 */

require_once __DIR__ . '/../app/Core/Config.php';
$configClass = 'App\Core\Config';
$configClass::load(__DIR__ . '/../.env');

use App\Core\Config;

// Conexão com PostgreSQL
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

echo "🌱 Executando seed...\n\n";

// 1. Cria usuário admin padrão
$adminEmail = 'admin@prospec.com.br';
$adminPass = 'Prospec@2026';

// Verifica se já existe
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
$stmt->execute(['email' => $adminEmail]);
$existingAdmin = $stmt->fetch();

if ($existingAdmin) {
    echo "  ⏭️  Admin já existe (id: {$existingAdmin['id']})\n";
} else {
    $hash = password_hash($adminPass, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, active) VALUES (:name, :email, :hash, :role, true) RETURNING id");
    $stmt->execute([
        'name' => 'Admin Prospec',
        'email' => $adminEmail,
        'hash' => $hash,
        'role' => 'admin',
    ]);
    $adminId = $stmt->fetchColumn();
    echo "  ✅ Admin criado (id: {$adminId}, email: {$adminEmail})\n";
}

// 2. Pipeline stages (já criados via migration 004, mas verificamos)
$stageCount = $pdo->query("SELECT COUNT(*) FROM pipeline_stages")->fetchColumn();
if ($stageCount == 0) {
    $pdo->exec("
        INSERT INTO pipeline_stages (name, position, color, is_default) VALUES
        ('Novo', 1, '#6c5ce7', true),
        ('Contatado', 2, '#0984e3', false),
        ('Respondendo', 3, '#00cec9', false),
        ('Reunião', 4, '#fdcb6e', false),
        ('Proposta', 5, '#e17055', false),
        ('Fechado', 6, '#00b894', false),
        ('Perdido', 7, '#d63031', false)
    ");
    echo "  ✅ Pipeline stages criados (7 estágios)\n";
} else {
    echo "  ⏭️  Pipeline stages já existem ({$stageCount})\n";
}

// 3. Cria usuário manager de exemplo
$managerEmail = 'manager@prospec.com.br';
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
$stmt->execute(['email' => $managerEmail]);
if (!$stmt->fetch()) {
    $hash = password_hash('Manager@2026', PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, active) VALUES ('Gerente Exemplo', :email, :hash, 'manager', true)");
    $stmt->execute(['email' => $managerEmail, 'hash' => $hash]);
    echo "  ✅ Manager criado (email: {$managerEmail})\n";
} else {
    echo "  ⏭️  Manager já existe\n";
}

// 4. Cria usuário seller de exemplo
$sellerEmail = 'seller@prospec.com.br';
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
$stmt->execute(['email' => $sellerEmail]);
if (!$stmt->fetch()) {
    $hash = password_hash('Seller@2026', PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, active) VALUES ('Vendedor Exemplo', :email, :hash, 'seller', true)");
    $stmt->execute(['email' => $sellerEmail, 'hash' => $hash]);
    echo "  ✅ Seller criado (email: {$sellerEmail})\n";
} else {
    echo "  ⏭️  Seller já existe\n";
}

echo "\n✨ Seed concluído!\n";
echo "═══════════════════════════════════════\n";
echo "🔑 Credenciais de acesso:\n";
echo "   Admin:   admin@prospec.com.br / Prospec@2026\n";
echo "   Gerente: manager@prospec.com.br / Manager@2026\n";
echo "   Vendedor: seller@prospec.com.br / Seller@2026\n";
echo "═══════════════════════════════════════\n";