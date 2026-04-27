<?php
/**
 * GuestMiddleware — Redireciona usuários autenticados
 */

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Response;

class GuestMiddleware
{
    public function handle(): bool
    {
        if (Auth::check()) {
            Response::redirect('/dashboard')->send();
            return false;
        }
        return true;
    }
}