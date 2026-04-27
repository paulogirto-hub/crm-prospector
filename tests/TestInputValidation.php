<?php



use App\Core\Validator;
use App\Core\Csrf;

class TestInputValidation extends TestCase
{
    public function runAll(): void
    {
        $this->testValidatorEmail();
        $this->testValidatorRequired();
        $this->testValidatorMin();
        $this->testXSSSanitization();
        $this->testSQLInjectionHandling();
        $this->testCreateLeadValidation();
    }

    private function testValidatorEmail(): void
    {
        $this->assertTrue("Valid email passes", Validator::email('user@example.com'));
        $this->assertTrue("Valid email with subdomain passes", Validator::email('user@sub.example.com'));
        $this->assertFalse("Invalid email fails", Validator::email('notanemail'));
        $this->assertFalse("Empty email as invalid", Validator::email(''));
        $this->assertFalse("Email with spaces fails", Validator::email('user @example.com'));
    }

    private function testValidatorRequired(): void
    {
        $errors = Validator::make(
            ['name' => 'Test Company'],
            ['name' => 'required']
        );
        $this->assertEmpty("Required field with value passes", $errors);

        $errors = Validator::make(
            ['name' => ''],
            ['name' => 'required']
        );
        $this->assertNotEmpty("Required field with empty value fails", $errors);

        $errors = Validator::make(
            ['name' => null],
            ['name' => 'required']
        );
        $this->assertNotEmpty("Required field with null fails", $errors);
    }

    private function testValidatorMin(): void
    {
        $errors = Validator::make(
            ['name' => 'AB'],
            ['name' => 'min:2']
        );
        $this->assertEmpty("Min:2 with 2 chars passes", $errors);

        $errors = Validator::make(
            ['name' => 'A'],
            ['name' => 'min:2']
        );
        $this->assertNotEmpty("Min:2 with 1 char fails", $errors);
    }

    private function testXSSSanitization(): void
    {
        $xss = '<script>alert("XSS")</script>';
        $sanitized = Validator::sanitize($xss);
        // After htmlspecialchars: alert(&quot;XSS&quot;) — safe (not executable as HTML)
        $this->assertNotContains("Sanitized output has no <script> tags", '<script>', $sanitized);
        // The literal string 'alert(' still exists in the escaped form — that's fine
        // because it will be displayed as text, not executed as JavaScript

        // Test with event handler
        $xss2 = '<img src=x onerror="alert(1)">';
        $sanitized2 = Validator::sanitize($xss2);
        $this->assertNotContains("Sanitized output has no onerror", 'onerror', $sanitized2);
    }

    private function testSQLInjectionHandling(): void
    {
        // Test that SQL injection strings are sanitized (doesn't break the app)
        $sqli = "' OR 1=1 --";
        $sanitized = Validator::sanitize($sqli);
        $this->assertNotEmpty("SQL injection input doesn't crash sanitize()", $sanitized);

        // Test prepared statements — the real defense
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => "' OR 1=1 --"]);
        $results = $stmt->fetchAll();
        $this->assertCount("SQL injection via prepared statement returns 0 results", 0, $results);

        // Test another injection pattern
        $stmt2 = $this->pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt2->execute(['email' => 'admin@prospec.com.br\'; DROP TABLE users; --']);
        $results2 = $stmt2->fetchAll();
        $this->assertCount("SQL injection DROP TABLE returns 0 results", 0, $results2);

        // Verify users table still exists
        $check = $this->pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $this->assert("Users table still exists after injection test", $check !== false);
    }

    private function testCreateLeadValidation(): void
    {
        // Valid data
        $errors = Validator::make(
            ['email' => 'valid@company.com', 'name' => 'Test Lead'],
            ['email' => 'email', 'name' => 'required|min:2']
        );
        $this->assertEmpty("Valid lead data passes validation", $errors);

        // Invalid email
        $errors = Validator::make(
            ['email' => 'notanemail', 'name' => 'Test Lead'],
            ['email' => 'email', 'name' => 'required|min:2']
        );
        $this->assertNotEmpty("Invalid email fails validation", $errors);

        // Missing required field
        $errors = Validator::make(
            ['email' => 'valid@company.com', 'name' => ''],
            ['email' => 'email', 'name' => 'required']
        );
        $this->assertNotEmpty("Missing required name fails validation", $errors);
    }
}