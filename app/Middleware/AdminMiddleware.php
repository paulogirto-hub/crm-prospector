<?php
/**
 * AdminMiddleware — Verifica se o usuário é admin
 */

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Response;

class AdminMiddleware
{
    public function handle(): bool
    {
        if (!Auth::check()) {
            Response::redirect('/login')->send();
            return false;
        }

        if (!Auth::isAdmin()) {
            Response::abort(403, 'Acesso restrito a administradores.');
            return false;
        }

        return true;
    }
}