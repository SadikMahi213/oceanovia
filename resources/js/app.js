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
    shipping: 0,
    tax: 0,
    grandTotal: 0,
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
    // Load the authenticated user's own cart from the server AFTER login.
    // Discards any guest/localStorage cart (no merge) so a guest's cart is
    // never carried into the authenticated user's cart.
    async loadFromServer() {
        try {
            const resp = await fetch('/cart/load', {
                method: 'GET',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
            });
            if (resp.ok) {
                const data = await resp.json();
                this.items = data.items ?? [];
                this.updateSummary();
            }
        } catch (e) { /* silent fail */ }
    },
    addItem(product, quantity = 1) {
        quantity = Number(quantity) || 1;
        const existing = this.items.find(i => i.id === product.id);
        if (existing) {
            existing.quantity = (Number(existing.quantity) || 0) + quantity;
        } else {
            this.items.push({ ...product, quantity });
        }
        this.updateSummary();
        this.tryServerAdd(product, quantity);
    },
    async tryServerAdd(product, quantity = 1) {
        this._pendingAdd = true;
        try {
            await fetch('/cart/add', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
                body: JSON.stringify({ product_id: product.id, quantity }),
            });
        } catch (e) { /* silent fail for guests */ }
        finally { this._pendingAdd = false; }
    },
    removeItem(productId) {
        this.items = this.items.filter(i => i.id !== productId);
        this.updateSummary();
        if (document.querySelector('meta[name="user-authed"]')?.content === '1') {
            this.syncWithServer();
            fetch(`/cart/${productId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content, 'Accept': 'application/json' },
            }).catch(() => {});
        }
    },
    updateQuantity(productId, qty) {
        const item = this.items.find(i => i.id === productId);
        if (item) {
            item.quantity = Math.max(1, Number(qty) || 1);
            this.updateSummary();
            if (document.querySelector('meta[name="user-authed"]')?.content === '1') {
                // Persist quantity change to server
                const cartItemId = item.cartItemId;
                if (cartItemId) {
                    fetch('/cart/update', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
                        body: JSON.stringify({ cart_item_id: cartItemId, quantity: item.quantity }),
                    }).catch(() => {});
                } else {
                    this.syncWithServer();
                }
            }
        }
    },
    updateSummary() {
        this.count = this.items.reduce((sum, i) => sum + (Number(i.quantity) || 0), 0);
        this.total = this.items.reduce((sum, i) => sum + (Number(i.price) || 0) * (Number(i.quantity) || 0), 0);
        this.shipping = this.calculateShipping();
        this.tax = Math.round(this.total * 0.08 * 100) / 100;
        this.grandTotal = Math.round((this.total + this.shipping + this.tax) * 100) / 100;
        localStorage.setItem('cart', JSON.stringify(this.items));
    },
    calculateShipping() {
        if (this.items.length === 0) return 0;
        // Group by vendor (supplierId preferred, else sellerId)
        const groups = {};
        this.items.forEach(i => {
            const vendor = i.supplierId ? `supplier_${i.supplierId}` : (i.sellerId ? `seller_${i.sellerId}` : 'default');
            if (!groups[vendor]) groups[vendor] = { subtotal: 0, weight: 0 };
            groups[vendor].subtotal += (Number(i.price) || 0) * (Number(i.quantity) || 0);
            groups[vendor].weight += (Number(i.weight) || 0) * (Number(i.quantity) || 0);
        });
        let totalShipping = 0;
        for (const key in groups) {
            const g = groups[key];
            const heavy = g.weight > 5 ? 3 : 0;
            // Free over $50 per vendor, else $5.99 + heavy surcharge (mirrors ShippingService fallback)
            const cost = g.subtotal >= 50 ? 0 + heavy : 5.99 + heavy;
            totalShipping += cost;
        }
        // If no vendor info yet (old localStorage items), fallback to single vendor logic
        if (Object.keys(groups).length === 1 && groups['default']) {
            const g = groups['default'];
            const heavy = g.weight > 5 ? 3 : 0;
            return g.subtotal >= 50 ? 0 + heavy : 5.99 + heavy;
        }
        return Math.round(totalShipping * 100) / 100;
    },
    get shippingText() {
        return this.shipping === 0 ? 'Free' : `$${this.shipping.toFixed(2)}`;
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
        setTimeout(() => Alpine.store('cart')?.loadFromServer(), 800);
    }
    Alpine.store('menu').init();
});

window.Alpine = Alpine;
Alpine.start();
