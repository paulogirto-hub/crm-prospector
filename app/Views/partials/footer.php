<!-- Footer / Global JS -->
<script>
document.addEventListener('alpine:init', () => {
    Alpine.store('confirmModal', {
        show: false,
        title: '',
        message: '',
        onConfirm: null,
        onCancel: null,
        open(title, message, onConfirm, onCancel) {
            this.title = title;
            this.message = message;
            this.onConfirm = onConfirm;
            this.onCancel = onCancel;
            this.show = true;
        }
    });

    Alpine.store('promptModal', {
        show: false,
        title: '',
        message: '',
        value: '',
        placeholder: '',
        confirmText: 'Confirmar',
        onConfirm: null,
        onCancel: null,
        open(opts) {
            this.title = opts.title || '';
            this.message = opts.message || '';
            this.value = opts.value || '';
            this.placeholder = opts.placeholder || 'Digite aqui...';
            this.confirmText = opts.confirmText || 'Confirmar';
            this.onConfirm = opts.onConfirm;
            this.onCancel = opts.onCancel;
            this.show = true;
        }
    });

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
                if (res.status === 401) {
                    window.location.href = '/login';
                    return;
                }
                return await res.json();
            } catch (err) {
                console.error('API Error:', err);
                throw err;
            } finally {
                this.loading = false;
            }
        },
        
        flash(type, message) {
            // TODO: Implement toast notifications via Alpine
            const colors = {
                success: 'bg-green-900 border-green-500 text-green-200',
                error: 'bg-red-900 border-red-500 text-red-200',
                warning: 'bg-yellow-900 border-yellow-500 text-yellow-200',
                info: 'bg-blue-900 border-blue-500 text-blue-200',
            };
            console.log(`[${type}] ${message}`);
        }
    });
});

// Initialize Lucide icons after page load
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) {
        lucide.createIcons();
    }
});

// Also re-create icons after Alpine renders
document.addEventListener('alpine:initialized', () => {
    setTimeout(() => {
        if (window.lucide) lucide.createIcons();
    }, 100);
});
</script>