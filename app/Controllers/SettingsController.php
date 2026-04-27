<?php
/**
 * SettingsController — Configurações do sistema (admin)
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Flash;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\User;
use AppCoreLogger;

class SettingsController extends Controller
{
    /**
     * GET /settings — Página de configurações
     */
    public function index(): void
    {
        $this->requireLogin();

        $isAdmin = Auth::isAdmin();
        $stages = PipelineStage::ordered();

        $this->render('settings/index', [
            'title' => 'Configurações — Prospec CRM',
            'isAdmin' => $isAdmin,
            'stages' => $stages,
        ]);
    }

    /**
     * GET /settings/team — Lista usuários (admin only)
     */
    public function team(): void
    {
        $this->requireLogin();
        if (!Auth::isAdmin()) {
            Response::abort(403, 'Acesso restrito a administradores.');
        }

        $users = User::all('name ASC');
        $roles = ['admin' => 'Administrador', 'manager' => 'Gerente', 'seller' => 'Vendedor'];

        $this->render('settings/team', [
            'title' => 'Equipe — Prospec CRM',
            'users' => $users,
            'roles' => $roles,
        ]);
    }

    /**
     * POST /settings/stage/create — Criar pipeline stage
     */
    public function createStage(): void
    {
        $this->requireLogin();
        if (!Auth::isAdmin()) {
            Response::abort(403, 'Acesso restrito a administradores.');
        }

        $data = $this->request->only(['name', 'color', 'position']);

        // Sanitize
        $data['name'] = Validator::sanitize($data['name'] ?? '');
        $data['color'] = Validator::sanitize($data['color'] ?? '');
        $data['position'] = (int)($data['position'] ?? 0);
        $data['is_default'] = '0';
        // Set default color if empty
        if (empty($data['color'])) {
            $data['color'] = '#6c5ce7';
        }

        if (empty($data['name'])) {
            Flash::error('Nome do estágio é obrigatório.');
            Response::redirect('/settings')->send();
            return;
        }

        try {
            PipelineStage::create($data);
            Flash::success('Estágio criado com sucesso!');
            Response::redirect('/settings')->send();
        } catch (\Throwable $e) {
            Logger::error("SettingsController::createStage error", ["exception" => $e->getMessage()]);
            Flash::error('Erro ao criar estágio. Tente novamente.');
            Response::redirect('/settings')->send();
        }
    }

    /**
     * POST /settings/stage/{id} — Editar pipeline stage
     */
    public function editStage(string $id): void
    {
        $this->requireLogin();
        if (!Auth::isAdmin()) {
            Response::abort(403, 'Acesso restrito a administradores.');
        }

        $stage = PipelineStage::findById((int)$id);
        if (!$stage) {
            Flash::error('Estágio não encontrado.');
            Response::redirect('/settings')->send();
            return;
        }

        $data = $this->request->only(['name', 'color', 'position']);
        if (isset($data['position'])) {
            $data['position'] = (int)$data['position'];
        }

        try {
            PipelineStage::updateById((int)$id, $data);
            Flash::success('Estágio atualizado!');
            Response::redirect('/settings')->send();
        } catch (\Throwable $e) {
            Logger::error("SettingsController::editStage error", ["exception" => $e->getMessage()]);
            Flash::error('Erro ao atualizar estágio. Tente novamente.');
            Response::redirect('/settings')->send();
        }
    }

    /**
     * POST /settings/stage/{id}/delete — Deletar pipeline stage
     */
    public function deleteStage(string $id): void
    {
        $this->requireLogin();
        if (!Auth::isAdmin()) {
            Response::abort(403, 'Acesso restrito a administradores.');
        }

        $stage = PipelineStage::findById((int)$id);
        if (!$stage) {
            Flash::error('Estágio não encontrado.');
            Response::redirect('/settings')->send();
            return;
        }

        // Verificar se há leads nesse stage
        $leadsCount = Lead::count('pipeline_stage_id = :sid', ['sid' => (int)$id]);
        if ($leadsCount > 0) {
            Flash::error("Não é possível deletar: há {$leadsCount} lead(s) neste estágio. Mova-os antes de deletar.");
            Response::redirect('/settings')->send();
            return;
        }

        try {
            PipelineStage::deleteById((int)$id);
            Flash::success('Estágio removido!');
            Response::redirect('/settings')->send();
        } catch (\Throwable $e) {
            Logger::error("SettingsController::deleteStage error", ["exception" => $e->getMessage()]);
            Flash::error('Erro ao remover estágio. Tente novamente.');
            Response::redirect('/settings')->send();
        }
    }
}