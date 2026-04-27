<?php
/**
 * Paginator — Paginação para listas e views
 * MELH-006: BACK-04
 * 
 * Uso no controller:
 *   $paginator = Paginator::make($total, $page, $perPage);
 *   $offset = $paginator['offset'];
 *   // ... buscar dados com LIMIT/OFFSET
 *   $paginator['items'] = $data; // attach data
 *   $this->render('view', ['paginator' => $paginator]);
 * 
 * Uso na view:
 *   <?php if (isset($paginator)) echo Paginator::render($paginator); ?>
 */

namespace App\Core;

class Paginator
{
    /**
     * Cria array de paginação
     */
    public static function make(int $total, int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage)); // Max 100 por página
        $lastPage = max(1, (int) ceil($total / $perPage));
        $offset = ($page - 1) * $perPage;

        return [
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => $lastPage,
            'offset' => $offset,
            'from' => $total > 0 ? $offset + 1 : 0,
            'to' => min($offset + $perPage, $total),
            'has_pages' => $total > $perPage,
            'has_more' => $page < $lastPage,
        ];
    }

    /**
     * Página atual a partir de query string
     */
    public static function currentPage(): int
    {
        return max(1, (int)($_GET['page'] ?? 1));
    }

    /**
     * Per-page a partir de query string (com limite)
     */
    public static function perPage(int $default = 20): int
    {
        $perPage = (int)($_GET['per_page'] ?? $default);
        return max(1, min(100, $perPage));
    }

    /**
     * Renderiza HTML de paginação (Bootstrap 5 style)
     */
    public static function render(array $paginator, string $baseUrl = ''): string
    {
        if (!$paginator['has_pages']) {
            return '';
        }

        if (empty($baseUrl)) {
            $baseUrl = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
            // Preservar query params exceto page
            $query = $_GET;
            unset($query['page']);
            if (!empty($query)) {
                $baseUrl .= '?' . http_build_query($query) . '&';
            } else {
                $baseUrl .= '?';
            }
        } else {
            $baseUrl .= (str_contains($baseUrl, '?') ? '&' : '?');
        }

        $page = $paginator['page'];
        $lastPage = $paginator['last_page'];
        $total = $paginator['total'];

        $html = '<nav aria-label="Paginação" class="d-flex justify-content-between align-items-center flex-wrap gap-2">';
        
        // Info
        $html .= '<span class="text-muted small">Mostrando ' . $paginator['from'] . '-' . $paginator['to'] . ' de ' . $total . '</span>';
        
        // Pages
        $html .= '<ul class="pagination pagination-sm mb-0">';
        
        // Previous
        $html .= '<li class="page-item' . ($page <= 1 ? ' disabled' : '') . '">';
        $html .= '<a class="page-link" href="' . $baseUrl . 'page=' . max(1, $page - 1) . '" aria-label="Anterior">&laquo;</a>';
        $html .= '</li>';
        
        // Page numbers (show max 5 pages around current)
        $startPage = max(1, $page - 2);
        $endPage = min($lastPage, $page + 2);
        
        // First page + ellipsis
        if ($startPage > 1) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . 'page=1">1</a></li>';
            if ($startPage > 2) {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }
        
        // Page range
        for ($i = $startPage; $i <= $endPage; $i++) {
            $active = $i === $page ? ' active' : '';
            $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $baseUrl . 'page=' . $i . '">' . $i . '</a></li>';
        }
        
        // Last page + ellipsis
        if ($endPage < $lastPage) {
            if ($endPage < $lastPage - 1) {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . 'page=' . $lastPage . '">' . $lastPage . '</a></li>';
        }
        
        // Next
        $html .= '<li class="page-item' . ($page >= $lastPage ? ' disabled' : '') . '">';
        $html .= '<a class="page-link" href="' . $baseUrl . 'page=' . min($lastPage, $page + 1) . '" aria-label="Próximo">&raquo;</a>';
        $html .= '</li>';
        
        $html .= '</ul>';
        $html .= '</nav>';

        return $html;
    }

    /**
     * Gera array de páginas para APIs
     */
    public static function links(array $paginator, string $baseUrl = ''): array
    {
        if (empty($baseUrl)) {
            $baseUrl = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
        }

        $page = $paginator['page'];
        $lastPage = $paginator['last_page'];

        return [
            'first' => $baseUrl . '?page=1',
            'last' => $baseUrl . '?page=' . $lastPage,
            'prev' => $page > 1 ? $baseUrl . '?page=' . ($page - 1) : null,
            'next' => $page < $lastPage ? $baseUrl . '?page=' . ($page + 1) : null,
            'current' => $page,
            'total_pages' => $lastPage,
            'total_items' => $paginator['total'],
        ];
    }
}