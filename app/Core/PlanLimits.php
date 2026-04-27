<?php
/**
 * PlanLimits — Verifica limites por plano
 * 
 * Planos: Free, Pro, Enterprise
 * Limita leads, companies, users por plano.
 */

namespace App\Core;

use App\Core\Config;

class PlanLimits
{
    /**
     * Limites por plano
     */
    private static array $limits = [
        'free' => [
            'max_leads'       => 50,
            'max_companies'   => 100,
            'max_users'       => 1,
            'max_templates'   => 5,
            'max_searches'    => 5,
            'can_export'      => false,
            'can_api'         => false,
            'can_prospec'     => false,
        ],
        'pro' => [
            'max_leads'       => 500,
            'max_companies'   => 1000,
            'max_users'       => 5,
            'max_templates'   => 50,
            'max_searches'    => 50,
            'can_export'      => true,
            'can_api'         => false,
            'can_prospec'     => true,
        ],
        'enterprise' => [
            'max_leads'       => -1,      // ilimitado
            'max_companies'   => -1,
            'max_users'       => -1,
            'max_templates'   => -1,
            'max_searches'    => -1,
            'can_export'      => true,
            'can_api'         => true,
            'can_prospec'     => true,
        ],
    ];

    /**
     * Retorna limites do plano
     */
    public static function getLimits(string $plan): array
    {
        $plan = strtolower($plan);
        return self::$limits[$plan] ?? self::$limits['free'];
    }

    /**
     * Verifica se o usuário pode criar mais leads
     */
    public static function canCreateLead(int $userId): bool
    {
        $plan = self::getUserPlan($userId);
        $limits = self::getLimits($plan);
        if ($limits['max_leads'] === -1) return true;

        try {
            $pdo = \App\Core\Model::pdo();
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE assigned_to = :uid AND status = 'active'");
            $stmt->execute(['uid' => $userId]);
            $count = (int)$stmt->fetchColumn();
            return $count < $limits['max_leads'];
        } catch (\Throwable $e) {
            return true; // Em caso de erro, permite (fail-open)
        }
    }

    /**
     * Verifica se o usuário pode criar mais empresas
     */
    public static function canCreateCompany(int $userId): bool
    {
        $plan = self::getUserPlan($userId);
        $limits = self::getLimits($plan);
        if ($limits['max_companies'] === -1) return true;

        try {
            $pdo = \App\Core\Model::pdo();
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM companies WHERE created_by = :uid AND status = 'active'");
            $stmt->execute(['uid' => $userId]);
            $count = (int)$stmt->fetchColumn();
            return $count < $limits['max_companies'];
        } catch (\Throwable $e) {
            return true;
        }
    }

    /**
     * Verifica se o plano permite a quantidade de usuários
     */
    public static function canCreateUser(int $currentCount, string $plan = 'free'): bool
    {
        $limits = self::getLimits($plan);
        if ($limits['max_users'] === -1) return true;
        return $currentCount < $limits['max_users'];
    }

    /**
     * Verifica se o plano permite exportação
     */
    public static function canExport(string $plan = 'free'): bool
    {
        $limits = self::getLimits($plan);
        return $limits['can_export'];
    }

    /**
     * Verifica se o plano permite acesso à API
     */
    public static function canUseApi(string $plan = 'free'): bool
    {
        $limits = self::getLimits($plan);
        return $limits['can_api'];
    }

    /**
     * Verifica se o plano permite prospecção
     */
    public static function canProspec(string $plan = 'free'): bool
    {
        $limits = self::getLimits($plan);
        return $limits['can_prospec'];
    }

    /**
     * Verifica se o plano permite uso de IA (prospecção + AI)
     */
    public static function canUseAI(int $userId): bool
    {
        $plan = self::getUserPlan($userId);
        // Free: 3 AI uses/month (trial), Pro+: unlimited
        if (self::canProspec($plan)) return true;
        $used = self::countRecentSearches($userId);
        return $used < 3;
    }

    /**
     * Verifica se o plano permite buscas de prospecção
     */
    public static function canSearch(int $userId): bool
    {
        $plan = self::getUserPlan($userId);
        $limits = self::getLimits($plan);
        if ($limits['can_prospec'] ?? false) return true;
        // Free plan: allow up to max_searches/month (trial)
        $max = $limits['max_searches'] ?? 5;
        $used = self::countRecentSearches($userId);
        return $used < $max;
    }

    /**
     * Retorna quantas buscas/leads/companies restam no mês para o usuário
     */
    public static function getRemaining(string $resource, int $userId): int
    {
        $plan = self::getUserPlan($userId);
        $limits = self::getLimits($plan);

        if ($resource === 'searches') {
            $max = $limits['max_searches'] ?? ($limits['can_prospec'] ? 50 : 5);
            if ($max === -1) return 999;
            $used = self::countRecentSearches($userId);
            return max(0, $max - $used);
        }

        if ($resource === 'leads') {
            $max = $limits['max_leads'];
            if ($max === -1) return 999;
            try {
                $pdo = \App\Core\Model::pdo();
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE assigned_to = :uid AND status = 'active'");
                $stmt->execute(['uid' => $userId]);
                $used = (int)$stmt->fetchColumn();
                return max(0, $max - $used);
            } catch (\Throwable $e) {
                return $max;
            }
        }

        if ($resource === 'companies') {
            $max = $limits['max_companies'];
            if ($max === -1) return 999;
            try {
                $pdo = \App\Core\Model::pdo();
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM companies WHERE created_by = :uid AND status = 'active'");
                $stmt->execute(['uid' => $userId]);
                $used = (int)$stmt->fetchColumn();
                return max(0, $max - $used);
            } catch (\Throwable $e) {
                return $max;
            }
        }

        return 0;
    }

    /**
     * Conta buscas de prospecção no mês atual
     */
    private static function countRecentSearches(int $userId): int
    {
        try {
            $pdo = \App\Core\Model::pdo();
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM audit_log WHERE user_id = :uid AND action = 'prospec_search' AND created_at >= date_trunc('month', CURRENT_DATE)");
            $stmt->execute(['uid' => $userId]);
            return (int)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Retorna limite de leads do plano
     */
    public static function getLeadLimit(string $plan = 'free'): int
    {
        return self::getLimits($plan)['max_leads'];
    }

    /**
     * Retorna limite de empresas do plano
     */
    public static function getCompanyLimit(string $plan = 'free'): int
    {
        return self::getLimits($plan)['max_companies'];
    }

    /**
     * Retorna o plano do usuário (via DB ou sessão)
     */
    public static function getUserPlan(int $userId): string
    {
        try {
            $pdo = \App\Core\Model::pdo();
            $stmt = $pdo->prepare("
                SELECT p.slug FROM plans p
                JOIN users u ON u.plan_id = p.id
                WHERE u.id = :uid
            ");
            $stmt->execute(['uid' => $userId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result['slug'] ?? 'free';
        } catch (\Throwable $e) {
            return 'free';
        }
    }
}