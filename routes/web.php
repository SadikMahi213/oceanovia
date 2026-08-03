<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\CmsPageController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\RateLimiter;

// ─── Health Check ────────────────────────────────────────────────────────
Route::get('/health', function () {
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        return response()->json(['status' => 'ok', 'timestamp' => now()->toIso8601String()]);
    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
});

// ─── Sitemap ────────────────────────────────────────────────────────────
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// ─── Homepage ───────────────────────────────────────────────────────────
Route::get('/', [ProductController::class, 'home'])->name('home');

// ─── Public Pages (CMS, FAQ, Contact) ────────────────────────────────────
Route::get('/faq', [FaqController::class, 'index'])->name('faq');
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:contact')->name('contact.store');
Route::get('/pages/{slug}', [CmsPageController::class, 'show'])->name('pages.show');

// ─── Products ───────────────────────────────────────────────────────────
Route::prefix('products')->name('products.')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('index');
    Route::get('/search', [ProductController::class, 'index'])->name('search');
    Route::get('/sellers', [ProductController::class, 'sellers'])->name('sellers');
    Route::get('/deals', [ProductController::class, 'deals'])->name('deals');
    Route::get('/{slug}', [ProductController::class, 'show'])->name('show');
});

// ─── Product Reviews ────────────────────────────────────────────────────
Route::post('/products/{product}/reviews', [ReviewController::class, 'store'])
    ->middleware(['auth', 'verified', 'throttle:reviews'])
    ->name('products.reviews.store');

// ─── Categories ─────────────────────────────────────────────────────────
Route::prefix('categories')->name('categories.')->group(function () {
    Route::get('/', [CategoryController::class, 'index'])->name('index');
    Route::get('/{slug}', [CategoryController::class, 'show'])->name('show');
});

// ─── Cart ───────────────────────────────────────────────────────────────
Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/add', [CartController::class, 'add'])->middleware('throttle:cart')->name('add');
    Route::post('/update', [CartController::class, 'update'])->middleware('throttle:cart')->name('update');
    Route::delete('/{id}', [CartController::class, 'remove'])->middleware('throttle:cart')->name('remove');
    Route::post('/sync', [CartController::class, 'sync'])->middleware(['auth', 'throttle:cart'])->name('sync');
});

// ─── Checkout ───────────────────────────────────────────────────────────
Route::prefix('checkout')->name('checkout.')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [CheckoutController::class, 'index'])->name('index');
    Route::post('/', [CheckoutController::class, 'store'])->middleware('throttle:checkout')->name('store');
    Route::get('/stripe/{order}', [CheckoutController::class, 'stripe'])->name('stripe');
    Route::get('/success/{order}', [CheckoutController::class, 'success'])->name('success');
    Route::get('/cancel/{order}', [CheckoutController::class, 'cancel'])->name('cancel');
});

Route::post('/stripe/webhook', [CheckoutController::class, 'webhook'])->name('stripe.webhook');

// ─── Refunds (Customer) ──────────────────────────────────────────────────
Route::prefix('refunds')->name('refunds.')->middleware(['auth', 'verified', 'throttle:checkout'])->group(function () {
    Route::get('/create/{order}', [App\Http\Controllers\RefundController::class, 'create'])->name('create');
    Route::post('/{order}', [App\Http\Controllers\RefundController::class, 'store'])->name('store');
});

// ─── Admin Routes ────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

    // Products
    Route::get('products', [App\Http\Controllers\Admin\ProductController::class, 'index'])->name('products.index');

    // Categories
    Route::get('categories', [App\Http\Controllers\Admin\CategoryController::class, 'index'])->name('categories.index');

    // Users
    Route::get('users', [App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');

    Route::resource('tax-rates', App\Http\Controllers\Admin\TaxRateController::class)->except('show');
    Route::patch('/tax-rates/{taxRate}/toggle', [App\Http\Controllers\Admin\TaxRateController::class, 'toggle'])->name('tax-rates.toggle');
    Route::resource('coupons', App\Http\Controllers\Admin\CouponController::class)->except('show');
    Route::resource('announcements', App\Http\Controllers\Admin\AnnouncementController::class)->except('show');
    Route::resource('banners', App\Http\Controllers\Admin\BannerController::class)->except('show');
    Route::resource('cms-pages', App\Http\Controllers\Admin\CmsPageController::class)->except('show');
    Route::resource('brands', App\Http\Controllers\Admin\BrandController::class)->except('show');
    Route::resource('faqs', App\Http\Controllers\Admin\FaqController::class)->except('show');
    Route::resource('contact-messages', App\Http\Controllers\Admin\ContactMessageController::class)->only(['index', 'show']);
    Route::patch('contact-messages/{contactMessage}/read', [App\Http\Controllers\Admin\ContactMessageController::class, 'markRead'])->name('contact-messages.read');

    // Orders
    Route::get('orders', [App\Http\Controllers\Admin\OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [App\Http\Controllers\Admin\OrderController::class, 'show'])->name('orders.show');

    // Reviews
    Route::get('reviews', [App\Http\Controllers\Admin\ReviewController::class, 'index'])->name('reviews.index');
    Route::patch('reviews/{review}/approve', [App\Http\Controllers\Admin\ReviewController::class, 'approve'])->name('reviews.approve');
    Route::patch('reviews/{review}/reject', [App\Http\Controllers\Admin\ReviewController::class, 'reject'])->name('reviews.reject');



    Route::get('payouts', [App\Http\Controllers\Admin\PayoutController::class, 'index'])->name('payouts.index');
    Route::get('payouts/{payout}', [App\Http\Controllers\Admin\PayoutController::class, 'show'])->name('payouts.show');
    Route::post('payouts/{payout}/approve', [App\Http\Controllers\Admin\PayoutController::class, 'approve'])->name('payouts.approve');
    Route::post('payouts/{payout}/complete', [App\Http\Controllers\Admin\PayoutController::class, 'complete'])->name('payouts.complete');
    Route::post('payouts/{payout}/reject', [App\Http\Controllers\Admin\PayoutController::class, 'reject'])->name('payouts.reject');
    Route::get('refunds', [App\Http\Controllers\Admin\RefundController::class, 'index'])->name('refunds.index');
    Route::get('refunds/{refund}', [App\Http\Controllers\Admin\RefundController::class, 'show'])->name('refunds.show');
    Route::post('refunds/{refund}/approve', [App\Http\Controllers\Admin\RefundController::class, 'approve'])->name('refunds.approve');
    Route::post('refunds/{refund}/reject', [App\Http\Controllers\Admin\RefundController::class, 'reject'])->name('refunds.reject');

    // KYC Verifications
    Route::get('kyc', [App\Http\Controllers\Admin\KycVerificationController::class, 'index'])->name('kyc.index');
    Route::get('kyc/{kycVerification}', [App\Http\Controllers\Admin\KycVerificationController::class, 'show'])->name('kyc.show');
    Route::post('kyc/{kycVerification}/approve', [App\Http\Controllers\Admin\KycVerificationController::class, 'approve'])->name('kyc.approve');
    Route::post('kyc/{kycVerification}/reject', [App\Http\Controllers\Admin\KycVerificationController::class, 'reject'])->name('kyc.reject');

    // Shipping Methods
    Route::resource('shipping-methods', App\Http\Controllers\Admin\ShippingMethodController::class)->except('show');

    // Settings
    Route::get('settings', [App\Http\Controllers\Admin\SettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [App\Http\Controllers\Admin\SettingController::class, 'update'])->name('settings.update');

    // Inline edit (generic endpoint for list-page editing)
    Route::post('inline/save', [App\Http\Controllers\Admin\InlineEditController::class, 'save'])->name('inline.save');

    // Reports
    // Support Tickets
    Route::get('support-tickets', [App\Http\Controllers\Admin\SupportTicketController::class, 'index'])->name('support-tickets.index');
    Route::get('support-tickets/{ticket}', [App\Http\Controllers\Admin\SupportTicketController::class, 'show'])->name('support-tickets.show');
    Route::patch('support-tickets/{ticket}', [App\Http\Controllers\Admin\SupportTicketController::class, 'update'])->name('support-tickets.update');
    Route::post('support-tickets/{ticket}/close', [App\Http\Controllers\Admin\SupportTicketController::class, 'close'])->name('support-tickets.close');

    // Audit Logs
    Route::get('audit-logs', [App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('audit-logs/{auditLog}', [App\Http\Controllers\Admin\AuditLogController::class, 'show'])->name('audit-logs.show');

    // Commissions
    Route::get('commissions', [App\Http\Controllers\Admin\CommissionController::class, 'index'])->name('commissions.index');
    Route::get('commissions/{commission}', [App\Http\Controllers\Admin\CommissionController::class, 'show'])->name('commissions.show');
    Route::post('commissions/{commission}/mark-paid', [App\Http\Controllers\Admin\CommissionController::class, 'markPaid'])->name('commissions.mark-paid');

    Route::get('reports/sales', [App\Http\Controllers\Admin\ReportController::class, 'sales'])->name('reports.sales');
    Route::get('reports/sellers', [App\Http\Controllers\Admin\ReportController::class, 'sellers'])->name('reports.sellers');
    Route::get('reports/products', [App\Http\Controllers\Admin\ReportController::class, 'products'])->name('reports.products');
    Route::get('reports/orders', [App\Http\Controllers\Admin\ReportController::class, 'orders'])->name('reports.orders');
});



// ─── Orders ─────────────────────────────────────────────────────────────
Route::prefix('orders')->name('orders.')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [OrderController::class, 'index'])->name('index');
    Route::get('/{id}', [OrderController::class, 'show'])->name('show');
});

// ─── Wishlist ───────────────────────────────────────────────────────────
Route::prefix('wishlist')->name('wishlist.')->middleware('auth')->group(function () {
    Route::get('/', [WishlistController::class, 'index'])->name('index');
    Route::post('/toggle', [WishlistController::class, 'toggle'])->middleware('throttle:wishlist')->name('toggle');
});

// ─── Become Seller / Supplier (public auth) ─────────────────────────────
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/become-seller', [App\Http\Controllers\SellerController::class, 'becomeSeller'])->name('become.seller');
    Route::post('/become-supplier', [App\Http\Controllers\SupplierController::class, 'becomeSupplier'])->name('become.supplier');
});

// ─── Seller Dashboard ───────────────────────────────────────────────────
Route::prefix('seller')->name('seller.')->middleware(['auth', 'verified', 'role:seller'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\SellerController::class, 'dashboard'])->name('dashboard');
    Route::get('/products', [App\Http\Controllers\SellerController::class, 'products'])->name('products.index');
    Route::get('/products/create', [App\Http\Controllers\SellerController::class, 'productCreate'])->name('products.create');
    Route::post('/products', [App\Http\Controllers\SellerController::class, 'productStore'])->name('products.store');
    Route::get('/products/{product}/edit', [App\Http\Controllers\SellerController::class, 'productEdit'])->name('products.edit');
    Route::put('/products/{product}', [App\Http\Controllers\SellerController::class, 'productUpdate'])->name('products.update');
    Route::delete('/products/{product}', [App\Http\Controllers\SellerController::class, 'productDestroy'])->name('products.destroy');
    Route::get('/orders', [App\Http\Controllers\SellerController::class, 'orders'])->name('orders.index');
    Route::get('/orders/{order}', [App\Http\Controllers\SellerController::class, 'orderShow'])->name('orders.show');
    Route::patch('/orders/{order}/status', [App\Http\Controllers\SellerController::class, 'updateOrderStatus'])->name('orders.update-status');
    Route::get('/profile', [App\Http\Controllers\SellerController::class, 'profile'])->name('profile');
    Route::put('/profile', [App\Http\Controllers\SellerController::class, 'profileUpdate'])->name('profile.update');
    Route::get('/analytics', [App\Http\Controllers\SellerController::class, 'analytics'])->name('analytics');
    Route::get('/payouts', [App\Http\Controllers\Seller\PayoutController::class, 'index'])->name('payouts.index');
    Route::get('/payouts/create', [App\Http\Controllers\Seller\PayoutController::class, 'create'])->name('payouts.create');
    Route::post('/payouts', [App\Http\Controllers\Seller\PayoutController::class, 'store'])->name('payouts.store');
    // ─── New Seller Features ──────────────────────────────────────────────
    Route::get('/inventory', [App\Http\Controllers\SellerController::class, 'inventory'])->name('inventory.index');
    Route::post('/inventory/{product}/adjust', [App\Http\Controllers\SellerController::class, 'inventoryAdjustment'])->name('inventory.adjust');
    Route::get('/inventory/logs/{product}', [App\Http\Controllers\SellerController::class, 'inventoryLogs'])->name('inventory.logs');
    Route::get('/returns', [App\Http\Controllers\SellerController::class, 'returnRequests'])->name('returns.index');
    Route::get('/returns/{returnRequest}', [App\Http\Controllers\SellerController::class, 'returnRequestShow'])->name('returns.show');
    Route::patch('/returns/{returnRequest}', [App\Http\Controllers\SellerController::class, 'returnRequestUpdate'])->name('returns.update');
    Route::get('/messages', [App\Http\Controllers\SellerController::class, 'messages'])->name('messages.index');
    Route::get('/messages/{sellerMessage}', [App\Http\Controllers\SellerController::class, 'messageShow'])->name('messages.show');
    Route::post('/messages/{sellerMessage}/reply', [App\Http\Controllers\SellerController::class, 'messageReply'])->name('messages.reply');
    Route::get('/coupons', [App\Http\Controllers\SellerController::class, 'coupons'])->name('coupons.index');
    Route::get('/coupons/create', [App\Http\Controllers\SellerController::class, 'couponCreate'])->name('coupons.create');
    Route::post('/coupons', [App\Http\Controllers\SellerController::class, 'couponStore'])->name('coupons.store');
    Route::get('/coupons/{coupon}/edit', [App\Http\Controllers\SellerController::class, 'couponEdit'])->name('coupons.edit');
    Route::put('/coupons/{coupon}', [App\Http\Controllers\SellerController::class, 'couponUpdate'])->name('coupons.update');
    Route::delete('/coupons/{coupon}', [App\Http\Controllers\SellerController::class, 'couponDestroy'])->name('coupons.destroy');
    Route::get('/reviews', [App\Http\Controllers\SellerController::class, 'reviews'])->name('reviews.index');
    Route::post('/reviews/{review}/reply', [App\Http\Controllers\SellerController::class, 'reviewReply'])->name('reviews.reply');
    Route::get('/wallet', [App\Http\Controllers\SellerController::class, 'wallet'])->name('wallet.index');
    Route::get('/notifications', [App\Http\Controllers\SellerController::class, 'notifications'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [App\Http\Controllers\SellerController::class, 'markNotificationRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [App\Http\Controllers\SellerController::class, 'markAllNotificationsRead'])->name('notifications.read-all');
    Route::get('/reports', [App\Http\Controllers\SellerController::class, 'reports'])->name('reports.index');
    Route::post('/reports/export', [App\Http\Controllers\SellerController::class, 'exportReport'])->name('reports.export');
    Route::get('/settings', [App\Http\Controllers\SellerController::class, 'settings'])->name('settings.index');
    Route::put('/settings', [App\Http\Controllers\SellerController::class, 'settingsUpdate'])->name('settings.update');
    Route::put('/settings/password', [App\Http\Controllers\SellerController::class, 'updatePassword'])->name('settings.password');
    // ─── Payout Methods ──────────────────────────────────────────────────
    Route::get('/payouts/methods', [App\Http\Controllers\Seller\PayoutController::class, 'withdrawalMethods'])->name('payouts.methods');
    Route::post('/payouts/methods', [App\Http\Controllers\Seller\PayoutController::class, 'storeWithdrawalMethod'])->name('payouts.methods.store');
    Route::delete('/payouts/methods/{method}', [App\Http\Controllers\Seller\PayoutController::class, 'destroyWithdrawalMethod'])->name('payouts.methods.destroy');
});

// ─── Supplier Dashboard ─────────────────────────────────────────────────
Route::prefix('supplier')->name('supplier.')->middleware(['auth', 'verified', 'role:supplier'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\SupplierController::class, 'dashboard'])->name('dashboard');

    // ── Profile & KYC ──────────────────────────────────────────────────
    Route::get('/profile', [App\Http\Controllers\SupplierController::class, 'profile'])->name('profile');
    Route::put('/profile', [App\Http\Controllers\SupplierController::class, 'profileUpdate'])->name('profile.update');
    Route::get('/kyc', [App\Http\Controllers\SupplierController::class, 'kyc'])->name('kyc');
    Route::post('/kyc', [App\Http\Controllers\SupplierController::class, 'kycSubmit'])->name('kyc.submit');
    Route::get('/kyc/status', [App\Http\Controllers\SupplierController::class, 'kycStatus'])->name('kyc.status');

    // ── Inventory ──────────────────────────────────────────────────────
    Route::get('/inventory', [App\Http\Controllers\SupplierController::class, 'inventory'])->name('inventory.index');
    Route::get('/inventory/{inventory}/edit', [App\Http\Controllers\SupplierController::class, 'inventoryEdit'])->name('inventory.edit');
    Route::put('/inventory/{inventory}', [App\Http\Controllers\SupplierController::class, 'inventoryUpdate'])->name('inventory.update');
    Route::post('/inventory/{inventory}/adjust', [App\Http\Controllers\SupplierController::class, 'inventoryAdjust'])->name('inventory.adjust');
    Route::post('/inventory/{inventory}/transfer', [App\Http\Controllers\SupplierController::class, 'inventoryTransfer'])->name('inventory.transfer');
    Route::get('/inventory/logs/{product}', [App\Http\Controllers\SupplierController::class, 'inventoryLogs'])->name('inventory.logs');

    // ── Orders ─────────────────────────────────────────────────────────
    Route::get('/orders', [App\Http\Controllers\SupplierController::class, 'orders'])->name('orders.index');
    Route::get('/orders/new', [App\Http\Controllers\SupplierController::class, 'ordersNew'])->name('orders.new');
    Route::get('/orders/accepted', [App\Http\Controllers\SupplierController::class, 'ordersAccepted'])->name('orders.accepted');
    Route::get('/orders/shipped', [App\Http\Controllers\SupplierController::class, 'ordersShipped'])->name('orders.shipped');
    Route::get('/orders/delivered', [App\Http\Controllers\SupplierController::class, 'ordersDelivered'])->name('orders.delivered');
    Route::get('/orders/returned', [App\Http\Controllers\SupplierController::class, 'ordersReturned'])->name('orders.returned');
    Route::get('/orders/cancelled', [App\Http\Controllers\SupplierController::class, 'ordersCancelled'])->name('orders.cancelled');
    Route::get('/orders/{order}', [App\Http\Controllers\SupplierController::class, 'orderShow'])->name('orders.show');
    Route::patch('/orders/{order}/fulfill', [App\Http\Controllers\SupplierController::class, 'orderFulfill'])->name('orders.fulfill');
    Route::post('/orders/{order}/accept', [App\Http\Controllers\SupplierController::class, 'orderAccept'])->name('orders.accept');
    Route::post('/orders/{order}/reject', [App\Http\Controllers\SupplierController::class, 'orderReject'])->name('orders.reject');
    Route::post('/orders/{order}/packed', [App\Http\Controllers\SupplierController::class, 'orderMarkPacked'])->name('orders.packed');
    Route::post('/orders/{order}/ready', [App\Http\Controllers\SupplierController::class, 'orderMarkReady'])->name('orders.ready');
    Route::get('/orders/{order}/invoice', [App\Http\Controllers\SupplierController::class, 'orderInvoice'])->name('orders.invoice');
    Route::post('/orders/{order}/notes', [App\Http\Controllers\SupplierController::class, 'orderAddNote'])->name('orders.notes');

    // ── Shipping ────────────────────────────────────────────────────────
    Route::get('/shipping/zones', [App\Http\Controllers\SupplierController::class, 'shippingZones'])->name('shipping.zones');
    Route::post('/shipping/zones', [App\Http\Controllers\SupplierController::class, 'shippingZonesStore'])->name('shipping.zones.store');
    Route::put('/shipping/zones/{zone}', [App\Http\Controllers\SupplierController::class, 'shippingZonesUpdate'])->name('shipping.zones.update');
    Route::delete('/shipping/zones/{zone}', [App\Http\Controllers\SupplierController::class, 'shippingZonesDestroy'])->name('shipping.zones.destroy');
    Route::get('/shipping/zones/{zone}/rates', [App\Http\Controllers\SupplierController::class, 'shippingRates'])->name('shipping.rates');
    Route::post('/shipping/zones/{zone}/rates', [App\Http\Controllers\SupplierController::class, 'shippingRatesStore'])->name('shipping.rates.store');
    Route::put('/shipping/rates/{rate}', [App\Http\Controllers\SupplierController::class, 'shippingRatesUpdate'])->name('shipping.rates.update');
    Route::delete('/shipping/rates/{rate}', [App\Http\Controllers\SupplierController::class, 'shippingRatesDestroy'])->name('shipping.rates.destroy');

    // ── Returns ─────────────────────────────────────────────────────────
    Route::get('/returns', [App\Http\Controllers\SupplierController::class, 'returns'])->name('returns.index');
    Route::get('/returns/{returnRequest}', [App\Http\Controllers\SupplierController::class, 'returnShow'])->name('returns.show');
    Route::patch('/returns/{returnRequest}', [App\Http\Controllers\SupplierController::class, 'returnUpdate'])->name('returns.update');

    // ── Reviews ─────────────────────────────────────────────────────────
    Route::get('/reviews', [App\Http\Controllers\SupplierController::class, 'reviews'])->name('reviews.index');
    Route::post('/reviews/{review}/reply', [App\Http\Controllers\SupplierController::class, 'reviewReply'])->name('reviews.reply');
    Route::post('/reviews/{review}/report', [App\Http\Controllers\SupplierController::class, 'reviewReportFake'])->name('reviews.report');

    // ── Messages ────────────────────────────────────────────────────────
    Route::get('/messages', [App\Http\Controllers\SupplierController::class, 'messages'])->name('messages.index');
    Route::get('/messages/{supplierMessage}', [App\Http\Controllers\SupplierController::class, 'messageShow'])->name('messages.show');
    Route::post('/messages/{supplierMessage}/reply', [App\Http\Controllers\SupplierController::class, 'messageReply'])->name('messages.reply');

    // ── Wallet & Finance ────────────────────────────────────────────────
    Route::get('/wallet', [App\Http\Controllers\SupplierController::class, 'wallet'])->name('wallet.index');
    Route::post('/wallet/payout', [App\Http\Controllers\SupplierController::class, 'payoutRequest'])->name('wallet.payout');
    Route::get('/settlements', [App\Http\Controllers\SupplierController::class, 'settlements'])->name('settlements.index');

    // ── Customers ───────────────────────────────────────────────────────
    Route::get('/customers', [App\Http\Controllers\SupplierController::class, 'customers'])->name('customers.index');
    Route::get('/customers/{customer}', [App\Http\Controllers\SupplierController::class, 'customerShow'])->name('customers.show');

    // ── Notifications ───────────────────────────────────────────────────
    Route::get('/notifications', [App\Http\Controllers\SupplierController::class, 'notifications'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [App\Http\Controllers\SupplierController::class, 'markNotificationRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [App\Http\Controllers\SupplierController::class, 'markAllNotificationsRead'])->name('notifications.read-all');

    // ── Reports ─────────────────────────────────────────────────────────
    Route::get('/reports', [App\Http\Controllers\SupplierController::class, 'reports'])->name('reports.index');
    Route::get('/reports/generate/{type}', [App\Http\Controllers\SupplierController::class, 'reportGenerate'])->name('reports.generate');
    Route::post('/reports/export', [App\Http\Controllers\SupplierController::class, 'exportReport'])->name('reports.export');

    // ── Settings ────────────────────────────────────────────────────────
    Route::get('/settings', [App\Http\Controllers\SupplierController::class, 'settings'])->name('settings.index');
    Route::put('/settings', [App\Http\Controllers\SupplierController::class, 'updateSettings'])->name('settings.update');
    Route::put('/settings/password', [App\Http\Controllers\SupplierController::class, 'updatePassword'])->name('settings.password');
});

// ─── Shipping Rate Calculator ───────────────────────────────────────────
Route::post('/shipping/rates', [App\Http\Controllers\ShippingController::class, 'rates'])->name('shipping.rates');

// ─── Customer Panel / Account ─────────────────────────────────────────
Route::prefix('account')->name('customer.')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\CustomerController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [App\Http\Controllers\CustomerController::class, 'profile'])->name('profile');
    Route::put('/profile', [App\Http\Controllers\CustomerController::class, 'profileUpdate'])->name('profile.update');
    Route::put('/profile/password', [App\Http\Controllers\CustomerController::class, 'updatePassword'])->name('profile.password');

    // Addresses
    Route::get('/addresses', [App\Http\Controllers\CustomerController::class, 'addresses'])->name('addresses.index');
    Route::post('/addresses', [App\Http\Controllers\CustomerController::class, 'addressStore'])->name('addresses.store');
    Route::put('/addresses/{address}', [App\Http\Controllers\CustomerController::class, 'addressUpdate'])->name('addresses.update');
    Route::delete('/addresses/{address}', [App\Http\Controllers\CustomerController::class, 'addressDestroy'])->name('addresses.destroy');

    // Orders
    Route::get('/orders', [App\Http\Controllers\CustomerController::class, 'orders'])->name('orders.index');
    Route::get('/orders/filter/{status}', [App\Http\Controllers\CustomerController::class, 'ordersFilter'])->name('orders.filter');
    Route::get('/orders/{order}', [App\Http\Controllers\CustomerController::class, 'orderShow'])->name('orders.show');
    Route::post('/orders/{order}/cancel', [App\Http\Controllers\CustomerController::class, 'orderCancel'])->name('orders.cancel');
    Route::post('/orders/{order}/reorder', [App\Http\Controllers\CustomerController::class, 'orderReorder'])->name('orders.reorder');
    Route::get('/orders/{order}/invoice', [App\Http\Controllers\CustomerController::class, 'orderInvoice'])->name('orders.invoice');

    // Wishlist
    Route::get('/wishlist', [App\Http\Controllers\CustomerController::class, 'wishlist'])->name('wishlist.index');

    // Notifications
    Route::get('/notifications', [App\Http\Controllers\CustomerController::class, 'notifications'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [App\Http\Controllers\CustomerController::class, 'markNotificationRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [App\Http\Controllers\CustomerController::class, 'markAllNotificationsRead'])->name('notifications.read-all');

    // Reviews
    Route::get('/reviews', [App\Http\Controllers\CustomerController::class, 'reviews'])->name('reviews.index');
    Route::put('/reviews/{review}', [App\Http\Controllers\CustomerController::class, 'reviewUpdate'])->name('reviews.update');
    Route::delete('/reviews/{review}', [App\Http\Controllers\CustomerController::class, 'reviewDestroy'])->name('reviews.destroy');

    // Coupons
    Route::get('/coupons', [App\Http\Controllers\CustomerController::class, 'coupons'])->name('coupons.index');

    // Saved
    Route::get('/recently-viewed', [App\Http\Controllers\CustomerController::class, 'recentlyViewed'])->name('recently-viewed');

    // Wallet
    Route::get('/wallet', [App\Http\Controllers\CustomerController::class, 'wallet'])->name('wallet.index');

    // Settings & Security
    Route::get('/settings', [App\Http\Controllers\CustomerController::class, 'settings'])->name('settings.index');
    Route::put('/settings', [App\Http\Controllers\CustomerController::class, 'updateSettings'])->name('settings.update');
    Route::get('/security', [App\Http\Controllers\CustomerController::class, 'security'])->name('security.index');
});

// ─── Dashboard (Customer legacy shortcut) ──────────────────────────────
Route::get('/dashboard', [App\Http\Controllers\CustomerController::class, 'dashboard'])
    ->middleware(['auth', 'verified'])->name('dashboard');

// ─── Auth Routes (Breeze) ───────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
