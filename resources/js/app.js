import Alpine from 'alpinejs';

// Store for global state
Alpine.store('app', {
    darkMode: localStorage.getItem('darkMode') === 'true',
    init() {
        if (this.darkMode) {
            document.documentElement.classList.add('dark');
        }
    },
    toggleDarkMode() {
        this.darkMode = !this.darkMode;
        localStorage.setItem('darkMode', this.darkMode);
        if (this.darkMode) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    },
});

Alpine.store('cart', {
    items: [],
    count: 0,
    total: 0,
    _syncing: false,
    _pendingAdd: false,
    init() {
        this.loadCart();
    },
    loadCart() {
        const stored = localStorage.getItem('cart');
        if (stored) {
            try {
                this.items = JSON.parse(stored);
                this.updateSummary();
            } catch (e) { /* corrupt data, reset */ }
        }
    },
    async syncWithServer() {
        if (this._syncing) return;
        this._syncing = true;
        try {
            const resp = await fetch('/cart/sync', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
                body: JSON.stringify({ items: this.items }),
            });
            if (resp.ok) {
                const data = await resp.json();
                if (data.synced) {
                    this.items = data.items ?? [];
                    this.updateSummary();
                }
            }
        } catch (e) { /* silent fail */ }
        finally { this._syncing = false; }
    },
    addItem(product) {
        const existing = this.items.find(i => i.id === product.id);
        if (existing) {
            existing.quantity += 1;
        } else {
            this.items.push({ ...product, quantity: 1 });
        }
        this.updateSummary();
        this.tryServerAdd(product);
    },
    async tryServerAdd(product) {
        this._pendingAdd = true;
        try {
            await fetch('/cart/add', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
                body: JSON.stringify({ product_id: product.id, quantity: 1 }),
            });
        } catch (e) { /* silent fail for guests */ }
        finally { this._pendingAdd = false; }
    },
    removeItem(productId) {
        this.items = this.items.filter(i => i.id !== productId);
        this.updateSummary();
    },
    updateQuantity(productId, qty) {
        const item = this.items.find(i => i.id === productId);
        if (item) {
            item.quantity = Math.max(1, qty);
            this.updateSummary();
        }
    },
    updateSummary() {
        this.count = this.items.reduce((sum, i) => sum + i.quantity, 0);
        this.total = this.items.reduce((sum, i) => sum + i.price * i.quantity, 0);
        localStorage.setItem('cart', JSON.stringify(this.items));
    },
    clearCart() {
        this.items = [];
        this.updateSummary();
    },
});

Alpine.store('wishlist', {
    items: [],
    count: 0,
    init() {
        const stored = localStorage.getItem('wishlist');
        if (stored) {
            try {
                this.items = JSON.parse(stored);
                this.count = this.items.length;
            } catch (e) { /* corrupt data, reset */ }
        }
    },
    async toggle(productId) {
        const idx = this.items.indexOf(productId);
        if (idx > -1) {
            this.items.splice(idx, 1);
        } else {
            this.items.push(productId);
        }
        this.count = this.items.length;
        localStorage.setItem('wishlist', JSON.stringify(this.items));
        try {
            await fetch('/wishlist/toggle', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
                body: JSON.stringify({ product_id: productId }),
            });
        } catch (e) { /* silent fail for guests */ }
    },
    has(productId) {
        return this.items.includes(productId);
    },
});

// Toast notification system
Alpine.store('toast', {
    notifications: [],
    show(message, type = 'success') {
        const id = Date.now();
        this.notifications.push({ id, message, type });
        setTimeout(() => {
            this.notifications = this.notifications.filter(n => n.id !== id);
        }, 4000);
    },
    success(message) { this.show(message, 'success'); },
    error(message) { this.show(message, 'error'); },
    info(message) { this.show(message, 'info'); },
    warning(message) { this.show(message, 'warning'); },
});

// Search store for live autocomplete
Alpine.store('search', {
    query: '',
    results: [],
    loading: false,
    show: false,
    async search(val) {
        this.query = val;
        if (val.length < 2) { this.results = []; return; }
        this.loading = true;
        try {
            const resp = await fetch(`/api/search/live?q=${encodeURIComponent(val)}`);
            if (resp.ok) this.results = await resp.json();
        } catch (e) { this.results = []; }
        finally { this.loading = false; }
    },
});

// Menu store for navigation data
Alpine.store('menu', {
    categories: [],
    async init() {
        try {
            const resp = await fetch('/api/menu/categories');
            if (resp.ok) this.categories = await resp.json();
        } catch (e) { /* silent fail */ }
    },
});

// Global keyboard shortcuts
document.addEventListener('keydown', (e) => {
    // Ctrl+Shift+L: Toggle dark mode
    if (e.ctrlKey && e.shiftKey && (e.key === 'L' || e.key === 'l')) {
        e.preventDefault();
        Alpine.store('app').toggleDarkMode();
    }
});

document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('meta[name="user-authed"]')?.content === '1') {
        setTimeout(() => Alpine.store('cart')?.syncWithServer(), 800);
    }
    Alpine.store('menu').init();
});

window.Alpine = Alpine;
Alpine.start();
