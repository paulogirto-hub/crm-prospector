<?php



use App\Core\RateLimit;

class TestRateLimit extends TestCase
{
    public function runAll(): void
    {
        $this->testLoginRateLimit();
        $this->testPostRateLimit();
        $this->testRateLimitExpiry();
        $this->testRateLimitClear();
    }

    private function testLoginRateLimit(): void
    {
        $key = 'login:test_login_rl';
        RateLimit::clear($key);

        // 5 attempts should be allowed
        for ($i = 0; $i < 5; $i++) {
            $this->assertTrue(
                "Login rate limit allows attempt " . ($i + 1) . "/5",
                RateLimit::check($key, 5, 900)
            );
            RateLimit::hit($key, 900);
        }

        // 6th attempt blocked
        $this->assertFalse(
            "Login rate limit blocks 6th attempt",
            RateLimit::check($key, 5, 900)
        );

        // Attempts count
        $this->assertEqual("Attempts count is 5", 5, RateLimit::attempts($key));

        RateLimit::clear($key);
    }

    private function testPostRateLimit(): void
    {
        $key = 'post:test_post_rl';
        RateLimit::clear($key);

        // 30 attempts should be allowed
        for ($i = 0; $i < 30; $i++) {
            $allowed = RateLimit::check($key, 30, 60);
            if (!$allowed) {
                $this->assert("POST rate limit allows request " . ($i + 1) . "/30", false, "Blocked too early");
                RateLimit::clear($key);
                return;
            }
            RateLimit::hit($key, 60);
        }

        // 31st attempt blocked
        $this->assertFalse(
            "POST rate limit blocks 31st request",
            RateLimit::check($key, 30, 60)
        );

        RateLimit::clear($key);
    }

    private function testRateLimitExpiry(): void
    {
        $key = 'login:test_expiry_rl';
        RateLimit::clear($key);

        // Hit with short decay (2 seconds)
        RateLimit::hit($key, 2);
        $this->assertEqual("Attempts after 1 hit is 1", 1, RateLimit::attempts($key));
        $this->assertTrue("Check still passes under limit", RateLimit::check($key, 5, 2));

        // availableAt should be within ~2 seconds from now
        $availableAt = RateLimit::availableAt($key);
        $diff = $availableAt - time();
        $this->assert("Rate limit expires in the future", $diff > 0, "Expires in {$diff}s");

        RateLimit::clear($key);
    }

    private function testRateLimitClear(): void
    {
        $key = 'login:test_clear_rl';

        // Hit multiple times
        for ($i = 0; $i < 5; $i++) {
            RateLimit::hit($key, 900);
        }
        $this->assertEqual("Before clear: 5 attempts", 5, RateLimit::attempts($key));

        // Clear
        RateLimit::clear($key);
        $this->assertEqual("After clear: 0 attempts", 0, RateLimit::attempts($key));
        $this->assertTrue("After clear: check passes", RateLimit::check($key, 5, 900));
    }
}