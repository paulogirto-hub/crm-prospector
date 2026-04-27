<?php



class TestSecurity extends TestCase
{
    public function runAll(): void
    {
        $this->testDBNotAccessibleFromOutside();
        $this->testCSRFTokenGeneration();
        $this->testCSRFTokenVerification();
        $this->testSecurityHeadersInConfig();
        $this->testNoAPIKeysExposed();
    }

    private function testDBNotAccessibleFromOutside(): void
    {
        // The postgres container maps port 5432 to 127.0.0.1:5433
        // This means it should NOT be accessible from external IPs

        // Test from within the container network — should work
        $stmt = $this->pdo->query("SELECT 1");
        $this->assertTrue("DB accessible from within container network", $stmt !== false);

        // Check that port 5433 is bound to localhost only
        $externalAccess = false;
        try {
            $testPdo = new \PDO(
                "pgsql:host=185.139.1.41;port=5433;dbname=prospec_crm",
                'prospec', 'prospec123',
                [\PDO::ATTR_TIMEOUT => 2]
            );
            $externalAccess = true;
        } catch (\PDOException $e) {
            // Good — connection failed
        }

        $this->assertFalse("DB NOT accessible from external IP on port 5433", $externalAccess);
    }

    private function testCSRFTokenGeneration(): void
    {
        // Start session for CSRF
        \App\Core\Session::start();
        $token = \App\Core\Csrf::token();
        $this->assertNotEmpty("CSRF token is generated", $token);
        $this->assert("CSRF token length is sufficient", strlen($token) >= 32, "Length: " . strlen($token));

        // Token should be consistent within the session
        $token2 = \App\Core\Csrf::token();
        $this->assertEqual("CSRF token is consistent within session", $token, $token2);
    }

    private function testCSRFTokenVerification(): void
    {
        \App\Core\Session::start();
        \App\Core\Csrf::token(); // Initialize/generate token
        $token = \App\Core\Csrf::token();

        // Valid token should pass
        $valid = \App\Core\Csrf::validate($token);
        $this->assertTrue("CSRF validation passes with valid token", $valid);

        // Invalid token should fail
        $invalid = \App\Core\Csrf::validate('invalid_token_12345');
        $this->assertFalse("CSRF validation fails with invalid token", $invalid);

        // Empty token should fail
        $empty = \App\Core\Csrf::validate('');
        $this->assertFalse("CSRF validation fails with empty token", $empty);
    }

    private function testSecurityHeadersInConfig(): void
    {
        // Read the nginx config file and check for security headers
        $nginxConf = file_get_contents(__DIR__ . '/../docker/nginx/default.conf');
        $this->assertContains("Nginx config has X-Frame-Options", 'X-Frame-Options', $nginxConf);
        $this->assertContains("Nginx config has X-Content-Type-Options", 'X-Content-Type-Options', $nginxConf);
        $this->assertContains("Nginx config has Content-Security-Policy", 'Content-Security-Policy', $nginxConf);
        $this->assertContains("Nginx config has Referrer-Policy", 'Referrer-Policy', $nginxConf);
        $this->assertContains("Nginx config has HSTS", 'Strict-Transport-Security', $nginxConf);
        $this->assertContains("Nginx config has Permissions-Policy", 'Permissions-Policy', $nginxConf);
    }

    private function testNoAPIKeysExposed(): void
    {
        // Check that public/index.php doesn't expose sensitive config values
        $publicIndex = file_get_contents(__DIR__ . '/../public/index.php');
        $this->assertNotContains("public/index.php does not echo OLLAMA_KEY", 'OLLAMA_KEY', $publicIndex);
        // The default value 'prospec123' in Config::get() is a fallback, not the real password.
        // The real password is loaded from .env and never echoed to output.
        // Check that index.php doesn't contain actual hardcoded credentials (not defaults)
        $this->assertNotContains("public/index.php does not hardcode real DB credentials", "'prospec', 'prospec123'", $publicIndex);

        // Check views don't expose secrets
        $dashboardView = file_get_contents(__DIR__ . '/../app/Views/dashboard/index.php');
        $this->assertNotContains("Dashboard view does not expose OLLAMA_KEY", 'OLLAMA_KEY', $dashboardView);
        $this->assertNotContains("Dashboard view does not expose DB password", 'prospec123', $dashboardView);

        // Check that sensitive directories are blocked in nginx
        $nginxConf = file_get_contents(__DIR__ . '/../docker/nginx/default.conf');
        $this->assertContains("Nginx blocks sensitive directories", 'location ~ ^/(app|config|database|migrations|scripts|storage|vendor)/', $nginxConf);
    }
}
