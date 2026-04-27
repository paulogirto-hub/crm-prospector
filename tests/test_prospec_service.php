<?php
/**
 * Testes — ProspecService Fallback
 */

class TestProspecService
{
    public static function run(): void
    {
        if (!class_exists('App\Core\ProspecService')) {
            require_once __DIR__ . '/../app/Core/ProspecService.php';
        }

        assert_true('ProspecService class exists', class_exists('App\Core\ProspecService'));

        // Verificar métodos existem
        $methods = ['search', 'getStatus', 'getHistory', 'enrich', 'score', 'analyze', 'getLead'];
        foreach ($methods as $method) {
            assert_test("ProspecService::{$method} exists", method_exists('App\Core\ProspecService', $method));
        }

        // Verificar que a classe tem o método getFriendlyCurlError (proteção de fallback)
        // Esse método é private, mas podemos verificar via reflection
        $ref = new ReflectionClass('App\Core\ProspecService');
        assert_true('Has getFriendlyCurlError method', $ref->hasMethod('getFriendlyCurlError'));

        // Verificar que o método request lida com erros
        $requestMethod = $ref->getMethod('request');
        assert_true('request method exists', $requestMethod !== null);

        // Testar via reflexão o getFriendlyCurlError
        $friendlyMethod = $ref->getMethod('getFriendlyCurlError');
        $friendlyMethod->setAccessible(true);

        $timeoutMsg = $friendlyMethod->invoke(null, 'Operation timed out after 30 seconds');
        assert_test('Timeout error is friendly', stripos($timeoutMsg, 'demorou') !== false || stripos($timeoutMsg, 'sobrecarregado') !== false);

        $connMsg = $friendlyMethod->invoke(null, 'Could not resolve host');
        assert_test('Connection error is friendly', stripos($connMsg, 'conectar') !== false || stripos($connMsg, 'conexão') !== false);

        $sslMsg = $friendlyMethod->invoke(null, 'SSL certificate problem');
        assert_test('SSL error is friendly', stripos($sslMsg, 'segurança') !== false || stripos($sslMsg, 'SSL') !== false);

        $genericMsg = $friendlyMethod->invoke(null, 'Some unknown error');
        assert_test('Generic error is friendly', stripos($genericMsg, 'conexão') !== false || stripos($genericMsg, 'tente') !== false);
    }
}