class Toast {
    static container = null;

    static init() {
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.className = 'toast-container';
            document.body.appendChild(this.container);
        }
    }

    static show(message, type = 'error', duration = 5000) {
        this.init();

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <div class="toast-header">
                <i class="fas ${type === 'error' ? 'fa-exclamation-circle text-danger' : 'fa-check-circle text-success'} me-2"></i>
                <strong>${type === 'error' ? 'Error' : 'Success'}</strong>
                <button type="button" class="toast-close">&times;</button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        `;

        this.container.appendChild(toast);

        const close = toast.querySelector('.toast-close');
        close.addEventListener('click', () => this.hide(toast));

        setTimeout(() => this.hide(toast), duration);
    }

    static hide(toast) {
        if (!toast.classList.contains('hiding')) {
            toast.classList.add('hiding');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }
    }
}