<?php
/**
 * CsrfMiddleware — Valida CSRF em requisições POST/PUT/DELETE
 */

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Response;

class CsrfMiddleware
{
    /**
     * Executa o middleware de CSRF
     */
    public function handle(): bool
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return true;
        }

        $token = $_POST['_csrf'] 
            ?? $_SERVER['HTTP_X_CSRF_TOKEN'] 
            ?? null;

        if ($token && Csrf::validate($token)) {
            return true;
        }

        // CSRF falhou
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            http_response_code(419);
            echo json_encode(['error' => 'Token CSRF inválido. Recarregue a página.']);
            return false;
        }

        Flash::error('Token de segurança expirado. Tente novamente.');
        Response::back()->send();
        return false;
    }
}