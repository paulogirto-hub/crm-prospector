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
<body class="bg-gray-950 text-gray-100 min-h-screen" x-data="{ sidebarOpen: true }">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <?php include dirname(__DIR__) . '/partials/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col" :class="sidebarOpen ? 'ml-64' : 'ml-0'" x-transition>
            <!-- Header -->
            <?php include dirname(__DIR__) . '/partials/header.php'; ?>
            
            <!-- Flash Messages -->
            <?php include dirname(__DIR__) . '/partials/flash.php'; ?>
            
            <!-- Page Content -->
            <main class="flex-1 p-6 overflow-auto">
                <?= $content ?>
            </main>
        </div>
    </div>
    
    <!-- Footer/JS -->
    <?php include dirname(__DIR__) . '/partials/footer.php'; ?>

    <!-- Global Modals -->
    <?php include dirname(__DIR__) . '/components/confirm-modal.php'; ?>
    <?php include dirname(__DIR__) . '/components/prompt-modal.php'; ?>
</body>
</html>