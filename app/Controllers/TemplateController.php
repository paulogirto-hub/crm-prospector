<?php
/**
 * TemplateController — CRUD de templates de mensagens
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Flash;
use App\Core\Response;
use App\Core\Validator;
use App\Core\AuditLog;
use App\Models\Template;
use AppCoreLogger;

class TemplateController extends Controller
{
    /**
     * GET /templates — Lista templates do usuário
     */
    public function index(): void
    {
        $this->requireLogin();

        $userId = Auth::userId();
        $templates = Template::findByUser($userId);

        $this->render('templates/index', [
            'title' => 'Templates — Prospec CRM',
            'templates' => $templates,
        ]);
    }

    /**
     * GET /templates/create — Form novo template
     */
    public function create(): void
    {
        $this->requireLogin();

        $this->render('templates/create', [
            'title' => 'Novo Template — Prospec CRM',
        ]);
    }

    /**
     * POST /templates — Salvar template
     */
    public function store(): void
    {
        $this->requireLogin();

        $data = $this->request->only(['name', 'niche', 'channel', 'subject', 'body', 'variables']);

        // Sanitize inputs
        $data['name'] = Validator::sanitize($data['name'] ?? '');
        $data['subject'] = Validator::sanitize($data['subject'] ?? '');
        $data['niche'] = Validator::sanitize($data['niche'] ?? '');
        // Body is purposefully HTML-capable, only trim
        $data['body'] = trim($data['body'] ?? '');

        $errors = $this->validate($data, [
            'name' => 'required|min:2|max:255',
            'channel' => 'required|in:email,whatsapp,instagram,linkedin',
            'body' => 'required',
        ]);

        if (!empty($errors)) {
            Flash::error('Corrija os erros abaixo.');
            $this->render('templates/create', [
                'title' => 'Novo Template — Prospec CRM',
                'errors' => $errors,
                'old' => $data,
            ]);
            return;
        }

        try {
            $data['user_id'] = Auth::userId();
            $data['variables'] = is_array($data['variables'] ?? null) ? json_encode($data['variables']) : ($data['variables'] ?? '[]');

            Template::create($data);

            // Audit log
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            AuditLog::create([
                'user_id' => Auth::userId(),
                'action' => 'template_created',
                'entity_type' => 'template',
                'entity_id' => null,
                'details' => json_encode(['name' => $data['name']]),
                'ip' => $ip,
            ]);

            Flash::success('Template criado com sucesso!');
            Response::redirect('/templates')->send();
        } catch (\Throwable $e) {
            Logger::error("TemplateController::store error", ["exception" => $e->getMessage()]);
            Flash::error('Erro ao criar template. Tente novamente.');
            $this->render('templates/create', [
                'title' => 'Novo Template — Prospec CRM',
                'errors' => [],
                'old' => $data,
            ]);
        }
    }

    /**
     * GET /templates/{id}/edit — Editar template
     */
    public function edit(string $id): void
    {
        $this->requireLogin();

        $template = Template::findById((int)$id);
        if (!$template || $template['user_id'] !== Auth::userId()) {
            Flash::error('Template não encontrado.');
            Response::redirect('/templates')->send();
            return;
        }

        $this->render('templates/edit', [
            'title' => 'Editar Template — Prospec CRM',
            'template' => $template,
        ]);
    }

    /**
     * POST /templates/{id} — Atualizar template
     */
    public function update(string $id): void
    {
        $this->requireLogin();

        $template = Template::findById((int)$id);
        if (!$template || $template['user_id'] !== Auth::userId()) {
            Flash::error('Template não encontrado.');
            Response::redirect('/templates')->send();
            return;
        }

        $data = $this->request->only(['name', 'niche', 'channel', 'subject', 'body', 'variables']);

        // Sanitize
        $data['name'] = Validator::sanitize($data['name'] ?? '');
        $data['subject'] = Validator::sanitize($data['subject'] ?? '');
        $data['niche'] = Validator::sanitize($data['niche'] ?? '');
        $data['body'] = trim($data['body'] ?? '');

        $errors = $this->validate($data, [
            'name' => 'required|min:2|max:255',
            'channel' => 'required|in:email,whatsapp,instagram,linkedin',
            'body' => 'required',
        ]);

        if (!empty($errors)) {
            Flash::error('Corrija os erros abaixo.');
            $this->render('templates/edit', [
                'title' => 'Editar Template — Prospec CRM',
                'template' => $template,
                'errors' => $errors,
                'old' => $data,
            ]);
            return;
        }

        try {
            $data['variables'] = is_array($data['variables'] ?? null) ? json_encode($data['variables']) : ($data['variables'] ?? '[]');

            Template::updateById((int)$id, $data);

            Flash::success('Template atualizado com sucesso!');
            Response::redirect('/templates')->send();
        } catch (\Throwable $e) {
            Logger::error("TemplateController::update error", ["exception" => $e->getMessage()]);
            Flash::error('Erro ao atualizar template. Tente novamente.');
            Response::redirect('/templates/' . $id . '/edit')->send();
        }
    }

    /**
     * POST /templates/{id}/delete — Deletar template
     */
    public function delete(string $id): void
    {
        $this->requireLogin();

        $template = Template::findById((int)$id);
        if (!$template || $template['user_id'] !== Auth::userId()) {
            Flash::error('Template não encontrado.');
            Response::redirect('/templates')->send();
            return;
        }

        try {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            AuditLog::create([
                'user_id' => Auth::userId(),
                'action' => 'template_deleted',
                'entity_type' => 'template',
                'entity_id' => (int)$id,
                'details' => json_encode(['name' => $template['name'] ?? '']),
                'ip' => $ip,
            ]);

            Template::deleteById((int)$id);

            Flash::success('Template removido com sucesso.');
            Response::redirect('/templates')->send();
        } catch (\Throwable $e) {
            Logger::error("TemplateController::delete error", ["exception" => $e->getMessage()]);
            Flash::error('Erro ao remover template. Tente novamente.');
            Response::redirect('/templates')->send();
        }
    }
}