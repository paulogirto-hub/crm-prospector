<?php
/**
 * Router — Roteamento URL → Controller@method
 * 
 * Suporta: GET, POST, PUT, DELETE, grupos, middleware, parâmetros dinâmicos
 */

namespace App\Core;

class Router
{
    private static array $routes = [];
    private static array $groupStack = [];

    /**
     * Registra rota GET
     */
    public static function get(string $uri, string $action, array $middleware = []): void
    {
        self::addRoute('GET', $uri, $action, $middleware);
    }

    /**
     * Registra rota POST
     */
    public static function post(string $uri, string $action, array $middleware = []): void
    {
        self::addRoute('POST', $uri, $action, $middleware);
    }

    /**
     * Registra rota PUT
     */
    public static function put(string $uri, string $action, array $middleware = []): void
    {
        self::addRoute('PUT', $uri, $action, $middleware);
    }

    /**
     * Registra rota DELETE
     */
    public static function delete(string $uri, string $action, array $middleware = []): void
    {
        self::addRoute('DELETE', $uri, $action, $middleware);
    }

    /**
     * Registra rota para múltiplos métodos
     */
    public static function match(array $methods, string $uri, string $action, array $middleware = []): void
    {
        foreach ($methods as $method) {
            self::addRoute(strtoupper($method), $uri, $action, $middleware);
        }
    }

    /**
     * Cria um grupo de rotas com prefixo e/ou middleware
     */
    public static function group(array $attributes, callable $callback): void
    {
        // Salva estado anterior
        $previousGroup = end(self::$groupStack) ?: ['prefix' => '', 'middleware' => []];
        
        $newGroup = [
            'prefix' => $previousGroup['prefix'] . ($attributes['prefix'] ?? ''),
            'middleware' => array_merge(
                $previousGroup['middleware'] ?? [],
                $attributes['middleware'] ?? []
            ),
        ];
        
        self::$groupStack[] = $newGroup;
        $callback();
        array_pop(self::$groupStack);
    }

    /**
     * Adiciona uma rota à lista
     */
    private static function addRoute(string $method, string $uri, string $action, array $middleware = []): void
    {
        // Aplica prefixo do grupo
        $group = end(self::$groupStack) ?: ['prefix' => '', 'middleware' => []];
        $uri = $group['prefix'] . $uri;
        
        // Merge middleware do grupo
        $middleware = array_merge($group['middleware'] ?? [], $middleware);

        // Normaliza URI
        $uri = '/' . trim($uri, '/');
        $uri = preg_replace('#/+#', '/', $uri);

        self::$routes[] = [
            'method' => $method,
            'uri' => $uri,
            'action' => $action,
            'middleware' => $middleware,
        ];
    }

    /**
     * Despacha a requisição para o controller correto
     */
    public static function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $uri = '/' . trim(parse_url($uri, PHP_URL_PATH) ?? '/', '/');
        $uri = preg_replace('#/+#', '/', $uri);

        foreach (self::$routes as $route) {
            if ($route['method'] !== $method) continue;

            $params = self::matchRoute($route['uri'], $uri);
            if ($params === false) continue;

            // Encontrou a rota! Executa middleware
            foreach ($route['middleware'] as $mw) {
                $middlewareClass = "App\\Middleware\\{$mw}";
                if (class_exists($middlewareClass)) {
                    $middleware = new $middlewareClass();
                    $result = $middleware->handle();
                    if ($result === false) {
                        return; // Middleware bloqueou
                    }
                }
            }

            // Parse Controller@method
            self::callAction($route['action'], $params);
            return;
        }

        // Rota não encontrada
        Response::abort(404);
    }

    /**
     * Compara URI pattern com URI atual e extrai parâmetros
     */
    private static function matchRoute(string $pattern, string $uri): array|false
    {
        // Converte {param} para regex named groups
        $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', function ($m) {
            return "(?P<{$m[1]}>[^/]+)";
        }, $pattern);

        $regex = "#^{$regex}$#";

        if (preg_match($regex, $uri, $matches)) {
            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = urldecode($value);
                }
            }
            return $params;
        }

        return false;
    }

    /**
     * Chama a ação do controller
     */
    private static function callAction(string $action, array $params = []): void
    {
        if (!str_contains($action, '@')) {
            throw new \RuntimeException("Formato de ação inválido: {$action}. Use Controller@method");
        }

        [$controllerName, $methodName] = explode('@', $action, 2);
        $controllerClass = "App\\Controllers\\{$controllerName}";

        if (!class_exists($controllerClass)) {
            throw new \RuntimeException("Controller não encontrado: {$controllerClass}");
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $methodName)) {
            throw new \RuntimeException("Método não encontrado: {$controllerClass}@{$methodName}");
        }

        $response = call_user_func_array([$controller, $methodName], $params);

        // Se o controller retornou um Response, envia
        if ($response instanceof Response) {
            $response->send();
        } elseif (is_string($response)) {
            echo $response;
        }
    }

    /**
     * Retorna todas as rotas registradas (para debug)
     */
    public static function getRoutes(): array
    {
        return self::$routes;
    }
}