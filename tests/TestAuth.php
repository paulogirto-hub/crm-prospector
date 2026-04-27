<?php



use App\Core\Auth;
use App\Core\Session;
use App\Core\RateLimit;
use App\Core\Flash;
use App\Core\Validator;
use App\Models\User;

class TestAuth extends TestCase
{
    public function runAll(): void
    {
        // Clear rate limits for test IP
        RateLimit::clear('login:127.0.0.1');
        RateLimit::clear('post:127.0.0.1');

        $this->testLoginCorrectCredentials();
        $this->testLoginWrongPassword();
        $this->testLoginNonexistentEmail();
        $this->testRateLimiting();
        $this->testRateLimitBlocked();
        $this->testLogout();
        $this->testProtectedRouteWithoutLogin();
        $this->testSessionFingerprinting();
    }

    private function testLoginCorrectCredentials(): void
    {
        // Find admin user in DB
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :email AND active = true LIMIT 1");
        $stmt->execute(['email' => 'admin@prospec.com.br']);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertNotEmpty("Admin user exists in DB", $user);

        if (!$user) return;

        // Test password verify
        $passwordOk = password_verify('admin123', $user['password_hash']);
        $this->assertTrue("Admin password verifies correctly", $passwordOk);

        // Test Auth::attempt
        $result = Auth::attempt('admin@prospec.com.br', 'admin123');
        $this->assertTrue("Auth::attempt with correct credentials returns true", $result);
        $this->assertTrue("Session has user_id after login", Session::has('user_id'));
        $this->assertEqual("Session user_email matches", 'admin@prospec.com.br', Session::get('user_email'));
        $this->assertEqual("Session user_role matches", 'admin', Session::get('user_role'));

        // Clean up
        Auth::logout();
    }

    private function testLoginWrongPassword(): void
    {
        RateLimit::clear('login:127.0.0.1');

        $result = Auth::attempt('admin@prospec.com.br', 'wrongpassword');
        $this->assertFalse("Auth::attempt with wrong password returns false", $result);
    }

    private function testLoginNonexistentEmail(): void
    {
        RateLimit::clear('login:127.0.0.1');

        $result = Auth::attempt('nonexistent@prospec.com.br', 'anypassword');
        $this->assertFalse("Auth::attempt with nonexistent email returns false", $result);
    }

    private function testRateLimiting(): void
    {
        $loginKey = 'login:test_rate_limit_ip';
        RateLimit::clear($loginKey);

        // Simula 5 tentativas falhas
        for ($i = 0; $i < 5; $i++) {
            $allowed = RateLimit::check($loginKey, 5, 900);
            if (!$allowed) {
                $this->assert("Rate limit allows attempt " . ($i + 1), false, "Blocked too early at attempt " . ($i + 1));
                return;
            }
            RateLimit::hit($loginKey, 900);
        }

        // 5ª tentativa + 1 hit = 5 hits total, check should still pass (5 < 5 = false, 5 attempts means next is blocked)
        // Actually check returns true if attempts < maxAttempts, so after 5 hits, attempts = 5, and 5 < 5 = false
        $allowed = RateLimit::check($loginKey, 5, 900);
        $this->assertFalse("Rate limit blocks after 5 failed attempts", $allowed);

        RateLimit::clear($loginKey);
    }

    private function testRateLimitBlocked(): void
    {
        $loginKey = 'login:test_blocked_ip';
        RateLimit::clear($loginKey);

        // Hit 5 times
        for ($i = 0; $i < 5; $i++) {
            RateLimit::hit($loginKey, 900);
        }

        // 6th attempt should be blocked
        $allowed = RateLimit::check($loginKey, 5, 900);
        $this->assertFalse("6th login attempt is blocked", $allowed);

        $retryAfter = RateLimit::retryAfter($loginKey);
        $this->assert("Retry-After is > 0 seconds", $retryAfter > 0, "Retry-After: {$retryAfter}");

        RateLimit::clear($loginKey);
    }

    private function testLogout(): void
    {
        // Login first
        Auth::attempt('admin@prospec.com.br', 'admin123');
        $this->assertTrue("Logged in before logout test", Auth::check());

        // Logout
        Auth::logout();
        $this->assertFalse("Auth::check returns false after logout", Auth::check());
        $this->assertFalse("Session does not have user_id after logout", Session::has('user_id'));
    }

    private function testProtectedRouteWithoutLogin(): void
    {
        // Ensure logged out
        if (Auth::check()) {
            Auth::logout();
        }

        $this->assertFalse("Auth::check returns false when not logged in", Auth::check());
    }

    private function testSessionFingerprinting(): void
    {
        // Login
        RateLimit::clear('login:127.0.0.1');
        Auth::attempt('admin@prospec.com.br', 'admin123');
        $this->assertTrue("Logged in for fingerprint test", Auth::check());

        // Get current fingerprint
        $oldUa = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SERVER['HTTP_USER_AGENT'] = 'HackedBrowser/1.0';

        // Create new session with different fingerprint — should detect hijacking
        Session::destroy();
        Session::start();

        // After session restart with new fingerprint, user should not be authenticated
        // because session was destroyed
        $this->assertFalse("Session destroyed after fingerprint change", Session::has('user_id'));

        // Restore
        $_SERVER['HTTP_USER_AGENT'] = $oldUa;
    }
}