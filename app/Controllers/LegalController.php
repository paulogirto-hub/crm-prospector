<?php
/**
 * LegalController — Termos de uso e política de privacidade
 */

namespace App\Controllers;

use App\Core\Controller;

class LegalController extends Controller
{
    /**
     * GET /terms — Termos de Uso
     */
    public function terms(): void
    {
        $this->render('legal/terms', [
            'title' => 'Termos de Uso — Prospec CRM',
        ], 'guest');
    }

    /**
     * GET /privacy — Política de Privacidade
     */
    public function privacy(): void
    {
        $this->render('legal/privacy', [
            'title' => 'Política de Privacidade — Prospec CRM',
        ], 'guest');
    }
}