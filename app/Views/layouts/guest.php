<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= \App\Core\Csrf::meta() ?>
    <title><?= e($title ?? 'Prospec CRM') ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: {
                            900: '#0a0a0f',
                            800: '#12121a',
                            700: '#1a1a2e',
                            600: '#242442',
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Alpine.js -->
    <script defer src="/js/alpine.min.js"></script>
    
    <!-- Lucide Icons -->
    <script src="/js/lucide.min.js"></script>
    
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
    </style>
</head>
<body class="bg-gray-950 text-gray-100 min-h-screen">
    <!-- Top bar -->
    <nav class="bg-gray-900 border-b border-gray-800 px-6 py-4">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <h1 class="text-xl font-bold bg-gradient-to-r from-purple-400 to-cyan-400 bg-clip-text text-transparent">
                Prospec CRM
            </h1>
            <a href="/login" class="text-sm text-gray-400 hover:text-white transition-colors">
                Login →
            </a>
        </div>
    </nav>

    <!-- Flash Messages -->
    <?php include dirname(__DIR__) . '/partials/flash.php'; ?>

    <!-- Page Content -->
    <main>
        <?= $content ?>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 border-t border-gray-800 px-6 py-4 mt-12">
        <div class="max-w-7xl mx-auto flex items-center justify-between text-xs text-gray-500">
            <span>&copy; <?= date('Y') ?> Prospec CRM</span>
            <div class="flex gap-4">
                <a href="/terms" class="hover:text-gray-300">Termos de Uso</a>
                <a href="/privacy" class="hover:text-gray-300">Privacidade</a>
            </div>
        </div>
    </footer>

    <!-- Alpine.js store -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('app', {
                loading: false,
                async api(method, url, data = {}) {
                    this.loading = true;
                    try {
                        const token = document.querySelector('meta[name="csrf-token"]')?.content;
                        const res = await fetch(url, {
                            method,
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-Token': token,
                            },
                            body: method !== 'GET' ? JSON.stringify(data) : undefined,
                        });
                        return await res.json();
                    } finally {
                        this.loading = false;
                    }
                }
            });
        });
    </script>
</body>
</html>