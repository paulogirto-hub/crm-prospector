<?php
/**
 * AuthMiddleware — Verifica se o usuário está autenticado
 */

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Session;
use App\Core\Flash;
use App\Core\Response;

class AuthMiddleware
{
    /**
     * Executa o middleware. Retorna true para permitir, false para bloquear.
     */
    public function handle(): bool
    {
        if (Auth::check()) {
            return true;
        }

        // Se é AJAX, retorna 401
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            http_response_code(401);
            echo json_encode(['error' => 'Não autenticado']);
            return false;
        }

        Flash::error('Faça login para continuar.');
        Response::redirect('/login')->send();
        return false;
    }
}