/**
 * Prospec CRM — Alpine.js Global Store + Helpers
 */

document.addEventListener('alpine:init', () => {
    Alpine.store('app', {
        loading: false,
        sidebarOpen: true,

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
            // Create toast element
            const toast = document.createElement('div');
            const colors = {
                success: 'bg-green-900/90 border-green-500 text-green-200',
                error: 'bg-red-900/90 border-red-500 text-red-200',
                warning: 'bg-yellow-900/90 border-yellow-500 text-yellow-200',
                info: 'bg-blue-900/90 border-blue-500 text-blue-200',
            };
            toast.className = `fixed top-4 right-4 z-50 flex items-center gap-3 px-4 py-3 rounded-lg border shadow-lg min-w-[300px] ${colors[type] || colors.info}`;
            toast.innerHTML = `<span class="text-sm flex-1">${message}</span><button onclick="this.parentElement.remove()" class="text-gray-400 hover:text-white">&times;</button>`;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 5000);
        },

        confirm(message) {
            return window.confirm(message);
        }
    });
});

// Initialize Lucide icons
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) {
        lucide.createIcons();
    }
});