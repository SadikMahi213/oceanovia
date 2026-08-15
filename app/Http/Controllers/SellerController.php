<?php

namespace App\Http\Controllers;

use App\Models\Commission;
use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ReturnRequest;
use App\Models\Review;
use App\Models\ReviewReply;
use App\Models\SellerBalance;
use App\Models\SellerCoupon;
use App\Models\SellerMessage;
use App\Models\SellerProfile;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\AuditService;
use App\Services\RefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SellerController extends Controller
{
    public function dashboard(): View
    {
        $sellerId = auth()->id();

        $totalProducts = Product::where('seller_id', $sellerId)->count();

        $totalOrders = OrderItem::where('seller_id', $sellerId)
            ->distinct('order_id')
            ->count('order_id');

        $totalRevenue = OrderItem::where('seller_id', $sellerId)
            ->whereHas('order', fn($q) => $q->where('status', '!=', 'cancelled'))
            ->sum('subtotal') ?? 0;

        $pendingOrders = OrderItem::where('seller_id', $sellerId)
            ->where('status', 'pending')
            ->distinct('order_id')
            ->count('order_id');

        $processingOrders = OrderItem::where('seller_id', $sellerId)
            ->where('status', 'processing')
            ->distinct('order_id')
            ->count('order_id');

        $shippedOrders = OrderItem::where('seller_id', $sellerId)
            ->where('status', 'shipped')
            ->distinct('order_id')
            ->count('order_id');

        $deliveredOrders = OrderItem::where('seller_id', $sellerId)
            ->where('status', 'delivered')
            ->distinct('order_id')
            ->count('order_id');

        $cancelledOrders = OrderItem::where('seller_id', $sellerId)
            ->where('status', 'cancelled')
            ->distinct('order_id')
            ->count('order_id');

        $lowStockProducts = Product::where('seller_id', $sellerId)
            ->whereHas('inventory', fn($q) => $q->lowStock())
            ->with('inventory')
            ->get();

        $outOfStockProducts = Inventory::whereHas('product', fn($q) => $q->where('seller_id', $sellerId))
            ->where('stock_quantity', 0)
            ->with('product')
            ->count();

        $bestSellingProducts = Product::where('seller_id', $sellerId)
            ->where('total_sold', '>', 0)
            ->orderByDesc('total_sold')
            ->take(5)
            ->get();

        $totalEarnings = SellerBalance::bySeller($sellerId)->value('balance') ?? 0;

        $pendingBalance = Commission::bySeller($sellerId)->pending()->sum('amount') ?? 0;

        $profile = auth()->user()->sellerProfile;

        $recentReviews = Review::whereHas('product', fn($q) => $q->where('seller_id', $sellerId))
            ->with('product', 'user')
            ->latest()
            ->take(5)
            ->get();

        $recentNotifications = UserNotification::where('notifiable_id', $sellerId)
            ->where('notifiable_type', User::class)
            ->orderByRaw('CASE WHEN read_at IS NULL THEN 0 ELSE 1 END')
            ->latest()
            ->take(5)
            ->get();

        $monthlySales = Commission::bySeller($sellerId)
            ->where('created_at', '>=', now()->subMonths(6)->startOfMonth())
            ->get()
            ->groupBy(fn($item) => $item->created_at->format('Y-m'))
            ->map(fn($items, $key) => [
                'year'  => explode('-', $key)[0],
                'month' => explode('-', $key)[1],
                'total' => $items->sum('amount'),
            ])
            ->values()
            ->all();

        $recentOrders = Order::whereHas('items', fn($q) => $q->where('seller_id', $sellerId))
            ->with(['items' => fn($q) => $q->where('seller_id', $sellerId)->with('product'), 'user'])
            ->latest()
            ->take(5)
            ->get();

        return view('seller.dashboard', compact(
            'totalProducts',
            'totalOrders',
            'totalRevenue',
            'pendingOrders',
            'processingOrders',
            'shippedOrders',
            'deliveredOrders',
            'cancelledOrders',
            'lowStockProducts',
            'outOfStockProducts',
            'bestSellingProducts',
            'totalEarnings',
            'pendingBalance',
            'profile',
            'recentReviews',
            'recentNotifications',
            'monthlySales',
            'recentOrders',
        ));
    }

    public function products(): View
    {
        $products = Product::where('seller_id', auth()->id())
            ->with('inventory')
            ->latest()
            ->paginate(15);

        return view('seller.products.index', compact('products'));
    }

    public function productCreate(): View
    {
        $categories = \App\Models\Category::active()->ordered()->get();
        return view('seller.products.form', ['product' => null, 'categories' => $categories]);
    }

    public function productStore(Request $request): RedirectResponse
    {
        $profile = auth()->user()->sellerProfile;
        if (!$profile || $profile->status !== 'approved') {
            return redirect()->back()->with('error', 'Your seller account must be approved before adding products.');
        }

        $validated = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'short_description'=> ['nullable', 'string', 'max:500'],
            'price'            => ['required', 'numeric', 'min:0'],
            'compare_price'    => ['nullable', 'numeric', 'min:0'],
            'cost_per_item'    => ['nullable', 'numeric', 'min:0'],
            'category_id'      => ['nullable', 'exists:categories,id'],
            'sku'              => ['nullable', 'string', 'max:100'],
            'barcode'          => ['nullable', 'string', 'max:100'],
            'weight'           => ['nullable', 'numeric', 'min:0'],
            'height'           => ['nullable', 'numeric', 'min:0'],
            'width'            => ['nullable', 'numeric', 'min:0'],
            'length'           => ['nullable', 'numeric', 'min:0'],
            'material'         => ['nullable', 'string', 'max:255'],
            'colors'           => ['nullable', 'array'],
            'sizes'            => ['nullable', 'array'],
            'tags'             => ['nullable', 'array'],
            'status'           => ['required', 'in:published,draft,archived'],
            'is_featured'      => ['boolean'],
            'meta_title'       => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'images'           => ['nullable', 'array'],
            'images.*'         => ['image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        $validated['seller_id'] = auth()->id();
        $validated['slug'] = Str::slug($validated['name']) . '-' . Str::random(6);

        if ($request->hasFile('images')) {
            $paths = [];
            foreach ($request->file('images') as $image) {
                $paths[] = $image->store('products', 'public');
            }
            $validated['images'] = $paths;
        }

        $product = Product::create($validated);

        if ($request->filled('variants')) {
            foreach ($request->input('variants') as $variant) {
                if (!empty(array_filter($variant))) {
                    $product->variants()->create([
                        'sku'            => $variant['sku'] ?? null,
                        'price'          => is_numeric($variant['price'] ?? null) ? $variant['price'] : null,
                        'stock_quantity' => is_numeric($variant['stock'] ?? null) ? (int) $variant['stock'] : 0,
                        'color'          => $variant['color'] ?? null,
                        'size'           => $variant['size'] ?? null,
                        'weight'         => is_numeric($variant['weight'] ?? null) ? $variant['weight'] : null,
                    ]);
                }
            }
        }

        return redirect()->route('seller.products.index')
            ->with('success', 'Product created successfully.');
    }

    public function productEdit(Product $product): View
    {
        if ($product->seller_id !== auth()->id()) {
            abort(403);
        }

        $categories = \App\Models\Category::active()->ordered()->get();
        return view('seller.products.form', compact('product', 'categories'));
    }

    public function productUpdate(Request $request, Product $product): RedirectResponse
    {
        if ($product->seller_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'short_description'=> ['nullable', 'string', 'max:500'],
            'price'            => ['required', 'numeric', 'min:0'],
            'compare_price'    => ['nullable', 'numeric', 'min:0'],
            'cost_per_item'    => ['nullable', 'numeric', 'min:0'],
            'category_id'      => ['nullable', 'exists:categories,id'],
            'sku'              => ['nullable', 'string', 'max:100'],
            'barcode'          => ['nullable', 'string', 'max:100'],
            'weight'           => ['nullable', 'numeric', 'min:0'],
            'height'           => ['nullable', 'numeric', 'min:0'],
            'width'            => ['nullable', 'numeric', 'min:0'],
            'length'           => ['nullable', 'numeric', 'min:0'],
            'material'         => ['nullable', 'string', 'max:255'],
            'colors'           => ['nullable', 'array'],
            'sizes'            => ['nullable', 'array'],
            'tags'             => ['nullable', 'array'],
            'status'           => ['required', 'in:published,draft,archived'],
            'is_featured'      => ['boolean'],
            'meta_title'       => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'images'           => ['nullable', 'array'],
            'images.*'         => ['image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        if ($request->hasFile('images')) {
            $paths = $product->images ?? [];
            foreach ($request->file('images') as $image) {
                $paths[] = $image->store('products', 'public');
            }
            $validated['images'] = $paths;
        }

        $product->update($validated);

        if ($request->has('variants')) {
            $submittedIds = [];
            foreach ($request->input('variants') as $variant) {
                if (isset($variant['id']) && !empty($variant['id'])) {
                    $pv = ProductVariant::where('id', $variant['id'])
                        ->where('product_id', $product->id)
                        ->first();
                    if ($pv) {
                        $pv->update([
                            'sku'            => $variant['sku'] ?? $pv->sku,
                            'price'          => is_numeric($variant['price'] ?? null) ? $variant['price'] : $pv->price,
                            'stock_quantity' => is_numeric($variant['stock'] ?? null) ? (int) $variant['stock'] : $pv->stock_quantity,
                            'color'          => $variant['color'] ?? $pv->color,
                            'size'           => $variant['size'] ?? $pv->size,
                            'weight'         => is_numeric($variant['weight'] ?? null) ? $variant['weight'] : $pv->weight,
                        ]);
                        $submittedIds[] = $pv->id;
                    }
                } elseif (!empty(array_filter($variant))) {
                    $pv = $product->variants()->create([
                        'sku'            => $variant['sku'] ?? null,
                        'price'          => is_numeric($variant['price'] ?? null) ? $variant['price'] : null,
                        'stock_quantity' => is_numeric($variant['stock'] ?? null) ? (int) $variant['stock'] : 0,
                        'color'          => $variant['color'] ?? null,
                        'size'           => $variant['size'] ?? null,
                        'weight'         => is_numeric($variant['weight'] ?? null) ? $variant['weight'] : null,
                    ]);
                    $submittedIds[] = $pv->id;
                }
            }
            $product->variants()->whereNotIn('id', $submittedIds)->delete();
        }

        return redirect()->back()
            ->with('success', 'Product updated successfully.');
    }

    public function productDestroy(Product $product): RedirectResponse
    {
        if ($product->seller_id !== auth()->id()) {
            abort(403);
        }

        $product->delete();

        return redirect()->route('seller.products.index')
            ->with('success', 'Product deleted successfully.');
    }

    public function orders(): View
    {
        $orderIds = OrderItem::where('seller_id', auth()->id())
            ->distinct()
            ->pluck('order_id');

        $orders = Order::whereIn('id', $orderIds)
            ->with(['items' => fn($q) => $q->where('seller_id', auth()->id())->with('product'), 'user'])
            ->latest()
            ->paginate(15);

        return view('seller.orders.index', compact('orders'));
    }

    public function orderShow(Order $order): View
    {
        $items = $order->items()->where('seller_id', auth()->id())
            ->with('product')
            ->get();

        if ($items->isEmpty()) {
            abort(403);
        }

        return view('seller.orders.show', compact('order', 'items'));
    }

    public function updateOrderStatus(Request $request, Order $order): RedirectResponse
    {
        if ($order->items()->where('seller_id', auth()->id())->doesntExist()) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:processing,shipped,delivered,cancelled'],
        ]);

        $sellerItems = $order->items()->where('seller_id', auth()->id())->get();
        foreach ($sellerItems as $item) {
            $item->status = $validated['status'];
            $item->save();
        }

        $allStatuses = $order->items()->pluck('status');
        if ($allStatuses->every(fn($s) => $s === 'shipped')) {
            $order->status = 'shipped';
            $order->shipped_at = $order->shipped_at ?? now();
        } elseif ($allStatuses->every(fn($s) => $s === 'delivered')) {
            $order->status = 'delivered';
            $order->delivered_at = $order->delivered_at ?? now();
        } elseif ($allStatuses->contains('shipped') || $allStatuses->contains('delivered')) {
            $order->status = 'partial';
        } elseif ($allStatuses->every(fn($s) => $s === 'cancelled')) {
            $order->status = 'cancelled';
            $order->cancelled_at = $order->cancelled_at ?? now();
        }
        $order->save();

        return redirect()->back()->with('success', 'Order status updated to ' . ucfirst($validated['status']) . '.');
    }

    public function profile(): View
    {
        $profile = auth()->user()->sellerProfile;

        return view('seller.profile', compact('profile'));
    }

    public function profileUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_name'  => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'phone'       => ['nullable', 'string', 'max:20'],
            'address'     => ['nullable', 'string', 'max:500'],
            'website'     => ['nullable', 'url', 'max:255'],
            'facebook'    => ['nullable', 'string', 'max:255'],
            'instagram'   => ['nullable', 'string', 'max:255'],
            'twitter'     => ['nullable', 'string', 'max:255'],
            'youtube'     => ['nullable', 'string', 'max:255'],
            'store_logo'  => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'store_banner'=> ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        $profile = auth()->user()->sellerProfile;

        if ($request->hasFile('store_logo')) {
            $validated['store_logo'] = $request->file('store_logo')
                ->store('seller-logos', 'public');
        }

        if ($request->hasFile('store_banner')) {
            $validated['store_banner'] = $request->file('store_banner')
                ->store('seller-banners', 'public');
        }

        $profile->update($validated);

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }

    public function analytics(): View
    {
        $sellerId = auth()->id();

        $monthlySales = OrderItem::where('seller_id', $sellerId)
            ->whereHas('order', fn($q) => $q->where('status', '!=', 'cancelled'))
            ->where('created_at', '>=', now()->subMonths(12)->startOfMonth())
            ->get()
            ->groupBy(fn($item) => $item->created_at->format('Y-m'))
            ->map(fn($items, $key) => [
                'year'  => explode('-', $key)[0],
                'month' => explode('-', $key)[1],
                'total' => $items->sum('subtotal'),
            ])
            ->values();

        $topProducts = OrderItem::where('seller_id', $sellerId)
            ->whereHas('order', fn($q) => $q->where('status', '!=', 'cancelled'))
            ->select('product_id', 'product_name', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(subtotal) as total_revenue'))
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('total_revenue')
            ->take(10)
            ->get();

        $orderStats = OrderItem::where('seller_id', $sellerId)
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->select('orders.status', DB::raw('COUNT(DISTINCT orders.id) as total'))
            ->groupBy('orders.status')
            ->pluck('total', 'status');

        $monthlyRevenue = OrderItem::where('seller_id', $sellerId)
            ->whereHas('order', fn($q) => $q->where('status', '!=', 'cancelled'))
            ->where('created_at', '>=', now()->subMonths(6)->startOfMonth())
            ->get()
            ->groupBy(fn($item) => $item->created_at->format('Y-m'))
            ->map(fn($items, $key) => [
                'year'        => explode('-', $key)[0],
                'month'       => explode('-', $key)[1],
                'revenue'     => $items->sum('subtotal'),
                'order_count' => $items->pluck('order_id')->unique()->count(),
            ])
            ->values()
            ->all();

        return view('seller.analytics', compact('monthlySales', 'topProducts', 'orderStats', 'monthlyRevenue'));
    }

    public function becomeSeller(AuditService $auditService): RedirectResponse
    {
        $user = auth()->user();

        if ($user->isSeller()) {
            return redirect()->route('seller.dashboard');
        }

        if ($user->isAdmin()) {
            return redirect()->back()->with('error', 'Admins cannot become sellers.');
        }

        $oldRole = $user->role_type;

        SellerProfile::create([
            'user_id'    => $user->id,
            'store_name' => $user->name . "'s Store",
            'status'     => 'pending',
        ]);

        $user->role_type = 'seller';
        $user->save();

        $auditService->log('role.change', $user, ['role_type' => $oldRole], ['role_type' => 'seller']);

        return redirect()->route('seller.dashboard')
            ->with('success', 'Welcome! Your seller account is pending approval.');
    }

    public function inventory(): View
    {
        $sellerId = auth()->id();

        $inventory = Inventory::whereHas('product', fn($q) => $q->where('seller_id', $sellerId))
            ->with('product')
            ->paginate(15);

        return view('seller.inventory.index', compact('inventory'));
    }

    public function inventoryLogs(Product $product): View
    {
        if ($product->seller_id !== auth()->id()) {
            abort(403);
        }

        $logs = InventoryLog::where('product_id', $product->id)
            ->with('creator')
            ->latest()
            ->paginate(20);

        return view('seller.inventory.logs', compact('product', 'logs'));
    }

    public function inventoryAdjustment(Request $request, Product $product): RedirectResponse
    {
        if ($product->seller_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'quantity' => ['required', 'integer'],
            'reason'   => ['required', 'string'],
            'type'     => ['required', 'in:adjustment,addition,removal'],
        ]);

        $inventory = $product->inventory;

        if (!$inventory) {
            return redirect()->back()->with('error', 'No inventory record found for this product.');
        }

        $beforeQty = $inventory->stock_quantity;

        switch ($validated['type']) {
            case 'addition':
                $inventory->increment('stock_quantity', $validated['quantity']);
                break;
            case 'removal':
                $inventory->decrement('stock_quantity', $validated['quantity']);
                break;
            case 'adjustment':
                $inventory->update(['stock_quantity' => $validated['quantity']]);
                break;
        }

        $afterQty = $inventory->fresh()->stock_quantity;

        InventoryLog::create([
            'product_id'    => $product->id,
            'user_id'       => auth()->id(),
            'type'          => $validated['type'],
            'quantity'      => $validated['quantity'],
            'before_quantity' => $beforeQty,
            'after_quantity'  => $afterQty,
            'reason'        => $validated['reason'],
        ]);

        return redirect()->back()->with('success', 'Inventory adjusted successfully.');
    }

    public function returnRequests(): View
    {
        $sellerId = auth()->id();

        $returns = ReturnRequest::where('seller_id', $sellerId)
            ->with('order', 'user')
            ->latest()
            ->paginate(15);

        return view('seller.returns.index', compact('returns'));
    }

    public function returnRequestShow(ReturnRequest $return): View
    {
        if ($return->seller_id !== auth()->id()) {
            abort(403);
        }

        $return->load('order', 'user', 'order.items');

        return view('seller.returns.show', compact('return'));
    }

    public function returnRequestUpdate(Request $request, ReturnRequest $return, RefundService $refundService): RedirectResponse
    {
        if ($return->seller_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'status'       => ['required', 'in:approved,rejected'],
            'seller_note'  => ['nullable', 'string'],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $return->update([
            'status'      => $validated['status'],
            'seller_note' => $validated['seller_note'] ?? null,
        ]);

        if ($validated['status'] === 'approved') {
            $amount = $validated['refund_amount'] ?? $return->amount;

            $refundService->approve(
                $return->refund,
                auth()->id(),
                $validated['seller_note'] ?? ''
            );

            $return->update(['refund_amount' => $amount]);
        }

        return redirect()->back()->with('success', 'Return request ' . $validated['status'] . ' successfully.');
    }

    public function messages(): View
    {
        $sellerId = auth()->id();

        $messages = SellerMessage::where('seller_id', $sellerId)
            ->with('order', 'user')
            ->latest()
            ->paginate(20);

        return view('seller.messages.index', compact('messages'));
    }

    public function messageShow(SellerMessage $message): View
    {
        if ($message->seller_id !== auth()->id()) {
            abort(403);
        }

        $message->update(['is_read_by_seller' => true]);

        $message->load('order', 'user');

        return view('seller.messages.show', compact('message'));
    }

    public function messageReply(Request $request, SellerMessage $message): RedirectResponse
    {
        if ($message->seller_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'message' => ['required', 'string'],
        ]);

        SellerMessage::create([
            'order_id'           => $message->order_id,
            'seller_id'          => $message->seller_id,
            'user_id'            => $message->user_id,
            'message'            => $validated['message'],
            'is_read_by_customer' => false,
            'is_read_by_seller'   => true,
        ]);

        return redirect()->back()->with('success', 'Reply sent successfully.');
    }

    public function coupons(): View
    {
        $sellerId = auth()->id();

        $coupons = SellerCoupon::where('seller_id', $sellerId)
            ->latest()
            ->paginate(15);

        return view('seller.coupons.index', compact('coupons'));
    }

    public function couponCreate(): View
    {
        return view('seller.coupons.form', ['coupon' => null]);
    }

    public function couponStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code'             => ['required', 'string', 'max:50', 'unique:seller_coupons,code'],
            'type'             => ['required', 'in:percentage,fixed'],
            'value'            => ['required', 'numeric', 'min:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount'     => ['nullable', 'numeric', 'min:0'],
            'usage_limit'      => ['nullable', 'integer', 'min:1'],
            'per_user_limit'   => ['nullable', 'integer', 'min:1'],
            'starts_at'        => ['nullable', 'date'],
            'expires_at'       => ['nullable', 'date', 'after:starts_at'],
        ]);

        $validated['seller_id'] = auth()->id();

        SellerCoupon::create($validated);

        return redirect()->route('seller.coupons.index')
            ->with('success', 'Coupon created successfully.');
    }

    public function couponEdit(SellerCoupon $coupon): View
    {
        if ($coupon->seller_id !== auth()->id()) {
            abort(403);
        }

        return view('seller.coupons.form', compact('coupon'));
    }

    public function couponUpdate(Request $request, SellerCoupon $coupon): RedirectResponse
    {
        if ($coupon->seller_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'code'             => ['required', 'string', 'max:50', 'unique:seller_coupons,code,' . $coupon->id],
            'type'             => ['required', 'in:percentage,fixed'],
            'value'            => ['required', 'numeric', 'min:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount'     => ['nullable', 'numeric', 'min:0'],
            'usage_limit'      => ['nullable', 'integer', 'min:1'],
            'per_user_limit'   => ['nullable', 'integer', 'min:1'],
            'starts_at'        => ['nullable', 'date'],
            'expires_at'       => ['nullable', 'date', 'after:starts_at'],
        ]);

        $coupon->update($validated);

        return redirect()->route('seller.coupons.index')
            ->with('success', 'Coupon updated successfully.');
    }

    public function couponDestroy(SellerCoupon $coupon): RedirectResponse
    {
        if ($coupon->seller_id !== auth()->id()) {
            abort(403);
        }

        $coupon->delete();

        return redirect()->back()->with('success', 'Coupon deleted successfully.');
    }

    public function reviews(): View
    {
        $sellerId = auth()->id();

        $reviews = Review::whereHas('product', fn($q) => $q->where('seller_id', $sellerId))
            ->with('product', 'user')
            ->latest()
            ->paginate(15);

        return view('seller.reviews.index', compact('reviews'));
    }

    public function reviewReply(Request $request, Review $review): RedirectResponse
    {
        if ($review->product->seller_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        ReviewReply::updateOrCreate(
            ['review_id' => $review->id, 'seller_id' => auth()->id()],
            ['body' => $validated['body']]
        );

        return redirect()->back()->with('success', 'Reply submitted successfully.');
    }

    public function wallet(): View
    {
        $sellerId = auth()->id();

        $balance = SellerBalance::bySeller($sellerId)->first();

        $pendingCommissions = Commission::bySeller($sellerId)->pending()->sum('amount');

        $paidCommissions = Commission::bySeller($sellerId)->paid()->sum('amount');

        $recentTransactions = \App\Models\Transaction::where('accountable_type', \App\Models\SellerBalance::class)
            ->whereHas('accountable', fn ($q) => $q->where('seller_id', $sellerId))
            ->latest()
            ->take(10)
            ->get();

        return view('seller.wallet.index', compact('balance', 'pendingCommissions', 'paidCommissions', 'recentTransactions'));
    }

    public function notifications(): View
    {
        $sellerId = auth()->id();

        $notifications = UserNotification::where('notifiable_id', $sellerId)
            ->where('notifiable_type', User::class)
            ->latest()
            ->paginate(20);

        return view('seller.notifications.index', compact('notifications'));
    }

    public function markNotificationRead(UserNotification $notification): RedirectResponse
    {
        if ($notification->notifiable_id !== auth()->id()) {
            abort(403);
        }

        $notification->update(['read_at' => now()]);

        return redirect()->back();
    }

    public function markAllNotificationsRead(): RedirectResponse
    {
        UserNotification::where('notifiable_id', auth()->id())
            ->where('notifiable_type', User::class)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return redirect()->back()->with('success', 'All notifications marked as read.');
    }

    public function reports(): View
    {
        return view('seller.reports.index');
    }

    public function exportReport(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'type'      => ['required', 'in:sales,products,orders'],
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $sellerId = auth()->id();
        $dateFrom = $validated['date_from'] ?? now()->startOfMonth();
        $dateTo = $validated['date_to'] ?? now()->endOfMonth();

        $filename = 'seller-report-' . $validated['type'] . '-' . now()->format('Ymd') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($sellerId, $validated, $dateFrom, $dateTo) {
            $handle = fopen('php://output', 'w+');

            switch ($validated['type']) {
                case 'sales':
                    fputcsv($handle, ['Date', 'Order #', 'Product', 'Quantity', 'Unit Price', 'Subtotal', 'Status']);
                    OrderItem::where('seller_id', $sellerId)
                        ->whereBetween('created_at', [$dateFrom, $dateTo])
                        ->with('order')
                        ->chunk(100, function ($items) use ($handle) {
                            foreach ($items as $item) {
                                fputcsv($handle, [
                                    $item->created_at->format('Y-m-d'),
                                    $item->order->order_number ?? 'N/A',
                                    $item->product_name,
                                    $item->quantity,
                                    number_format($item->unit_price, 2),
                                    number_format($item->subtotal, 2),
                                    $item->status,
                                ]);
                            }
                        });
                    break;

                case 'products':
                    fputcsv($handle, ['Product Name', 'SKU', 'Price', 'Total Sold', 'Stock', 'Status']);
                    Product::where('seller_id', $sellerId)->chunk(100, function ($products) use ($handle) {
                        foreach ($products as $product) {
                            fputcsv($handle, [
                                $product->name,
                                $product->sku,
                                number_format($product->price, 2),
                                $product->total_sold,
                                $product->stock_quantity,
                                $product->status,
                            ]);
                        }
                    });
                    break;

                case 'orders':
                    fputcsv($handle, ['Order #', 'Customer', 'Date', 'Status', 'Total', 'Payment Method']);
                    $orderIds = OrderItem::where('seller_id', $sellerId)
                        ->distinct()
                        ->pluck('order_id');
                    Order::whereIn('id', $orderIds)
                        ->whereBetween('created_at', [$dateFrom, $dateTo])
                        ->with('user')
                        ->chunk(100, function ($orders) use ($handle) {
                            foreach ($orders as $order) {
                                fputcsv($handle, [
                                    $order->order_number,
                                    $order->user?->name ?? 'N/A',
                                    $order->created_at->format('Y-m-d'),
                                    $order->status,
                                    number_format($order->total, 2),
                                    $order->payment_method,
                                ]);
                            }
                        });
                    break;
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function settings(): View
    {
        return view('seller.settings.index');
    }

    public function settingsUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tax_settings'    => ['nullable', 'array'],
            'invoice_settings' => ['nullable', 'array'],
            'language'        => ['nullable', 'string'],
            'timezone'        => ['nullable', 'string'],
            'currency'        => ['nullable', 'string'],
        ]);

        $user = auth()->user();

        foreach ($validated as $key => $value) {
            if ($value !== null) {
                \App\Models\Setting::set('seller.' . $user->id . '.' . $key, $value, 'seller');
            }
        }

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', 'min:8', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[0-9]/', 'regex:/[@$!%*#?&]/'],
        ]);

        auth()->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->back()->with('success', 'Password updated successfully.');
    }
}
