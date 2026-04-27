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
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Alpine.js -->
    <script defer src="/js/alpine.min.js"></script>
    
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
    </style>
</head>
<body class="bg-gray-950 text-gray-100 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md px-6">
        <!-- Logo -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-400 to-cyan-400 bg-clip-text text-transparent">
                Prospec CRM
            </h1>
            <p class="text-gray-500 mt-2 text-sm">Sistema de Prospecção Comercial</p>
        </div>
        
        <!-- Flash Messages -->
        <?php include dirname(__DIR__) . '/partials/flash.php'; ?>
        
        <!-- Content -->
        <?= $content ?>
    </div>
    
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