<?php
/**
 * Controller — Base controller com helpers para views, redirect, JSON e validação
 */

namespace App\Core;

abstract class Controller
{
    protected Request $request;

    public function __construct()
    {
        $this->request = new Request();
    }

    /**
     * Renderiza uma view com layout
     */
    protected function render(string $view, array $data = [], string $layout = 'main'): void
    {
        // Extrai variáveis para a view
        extract($data, EXTR_SKIP);

        // CSRF token disponível em todas as views
        $csrf = Csrf::token();
        $csrfField = Csrf::field();

        // Usuário autenticado
        $authUser = Auth::user();

        // Flash messages
        $flashes = Flash::all();

        // Captura a view
        $viewPath = dirname(__DIR__) . "/Views/{$view}.php";
        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View não encontrada: {$viewPath}");
        }

        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        // Layout
        $layoutPath = dirname(__DIR__) . "/Views/layouts/{$layout}.php";
        if (!file_exists($layoutPath)) {
            echo $content;
            return;
        }

        require $layoutPath;
    }

    /**
     * Renderiza view sem layout (para AJAX/JSON responses)
     */
    protected function partial(string $view, array $data = []): string
    {
        extract($data, EXTR_SKIP);
        $viewPath = dirname(__DIR__) . "/Views/{$view}.php";
        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View não encontrada: {$viewPath}");
        }
        ob_start();
        require $viewPath;
        return ob_get_clean();
    }

    /**
     * Redireciona para uma URL
     */
    protected function redirect(string $url): Response
    {
        return Response::redirect($url);
    }

    /**
     * Redireciona de volta para a página anterior
     */
    protected function back(): Response
    {
        return Response::back();
    }

    /**
     * Retorna resposta JSON
     */
    protected function json(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    /**
     * Valida dados com regras
     * Retorna array de erros (vazio = válido)
     */
    protected function validate(array $data, array $rules): array
    {
        return Validator::make($data, $rules);
    }

    /**
     * Verifica se o usuário está autenticado
     */
    protected function requireLogin(): void
    {
        if (!Auth::check()) {
            Flash::error('Faça login para continuar.');
            Response::redirect('/login')->send();
            exit;
        }
    }

    /**
     * Verifica se o usuário tem role específico
     */
    protected function requireRole(string $role): void
    {
        $this->requireLogin();
        if (!Auth::hasRole($role)) {
            Response::abort(403, 'Acesso negado. Permissão insuficiente.');
        }
    }
}