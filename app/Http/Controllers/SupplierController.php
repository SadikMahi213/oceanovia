<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Refund;
use App\Models\ReturnRequest;
use App\Models\Review;
use App\Models\ReviewReply;
use App\Models\SupplierBalance;
use App\Models\SupplierMessage;
use App\Models\SupplierMessageReply;
use App\Models\SupplierPayout;
use App\Models\SupplierProfile;
use App\Models\SupplierShippingRate;
use App\Models\SupplierShippingZone;
use App\Models\Transaction;
use App\Models\UserNotification;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupplierController extends Controller
{
    /**
     * Month-expression that works on both SQLite (tests) and MySQL (production).
     */
    private static function monthExpression(): string
    {
        return DB::getDriverName() === 'mysql'
            ? "DATE_FORMAT(created_at, '%Y-%m')"
            : "strftime('%Y-%m', created_at)";
    }

    // ─── Dashboard ───────────────────────────────────────────────────────────

    public function dashboard(): View
    {
        $supplierId = auth()->id();
        $profile = auth()->user()->supplierProfile;

        $totalInventory = Inventory::where('supplier_id', $supplierId)->count();
        $lowStockCount = Inventory::where('supplier_id', $supplierId)->lowStock()->count();
        $outOfStockCount = Inventory::where('supplier_id', $supplierId)->outOfStock()->count();

        $totalOrders = OrderItem::where('supplier_id', $supplierId)->distinct('order_id')->count('order_id');
        $pendingOrders = OrderItem::where('supplier_id', $supplierId)->whereIn('order_items.status', ['pending', 'processing'])->distinct('order_id')->count('order_id');
        $processingOrders = OrderItem::where('supplier_id', $supplierId)->where('order_items.status', 'processing')->distinct('order_id')->count('order_id');
        $completedOrders = OrderItem::where('supplier_id', $supplierId)->where('order_items.status', 'delivered')->distinct('order_id')->count('order_id');
        $cancelledOrders = OrderItem::where('supplier_id', $supplierId)->where('order_items.status', 'cancelled')->distinct('order_id')->count('order_id');
        $returnedOrders = OrderItem::where('supplier_id', $supplierId)->where('order_items.status', 'returned')->distinct('order_id')->count('order_id');

        $totalRevenue = OrderItem::where('supplier_id', $supplierId)
            ->whereHas('order', fn($q) => $q->where('status', '!=', 'cancelled'))
            ->sum('subtotal');

        $balance = SupplierBalance::firstOrCreate(
            ['supplier_id' => $supplierId],
            ['balance' => 0, 'pending_balance' => 0, 'total_earned' => 0, 'total_withdrawn' => 0, 'platform_fees' => 0]
        );

        $lowStockItems = Inventory::where('supplier_id', $supplierId)
            ->lowStock()->with('product')->latest()->take(5)->get();

        $recentOrders = OrderItem::where('supplier_id', $supplierId)
            ->with(['order', 'product'])->latest()->take(10)->get();

        $monthlySales = OrderItem::where('supplier_id', $supplierId)
            ->whereHas('order', fn($q) => $q->where('status', 'delivered'))
            ->select(DB::raw(self::monthExpression().' as month'), DB::raw('SUM(subtotal) as total'), DB::raw('COUNT(DISTINCT order_id) as count'))
            ->groupBy('month')->orderBy('month', 'desc')->limit(12)->get();

        $topProducts = OrderItem::where('supplier_id', $supplierId)
            ->select('product_id', DB::raw('SUM(quantity) as total_sold'), DB::raw('SUM(subtotal) as total_revenue'))
            ->groupBy('product_id')->orderByDesc('total_sold')->with('product')->take(5)->get();

        $topCategories = OrderItem::where('supplier_id', $supplierId)
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select('categories.id', 'categories.name', DB::raw('COUNT(DISTINCT order_items.id) as total'))
            ->groupBy('categories.id', 'categories.name')->orderByDesc('total')->take(5)->get();

        $recentActivities = InventoryLog::whereHas('product.inventory', fn($q) => $q->where('supplier_id', $supplierId))
            ->with('product')->latest()->take(10)->get();

        $notifications = UserNotification::where('notifiable_id', $supplierId)
            ->where('notifiable_type', 'App\Models\User')->unread()->latest()->take(5)->get();

        $bestSellingProducts = $topProducts;
        $activeProducts = Product::whereHas('inventory', fn($q) => $q->where('supplier_id', $supplierId))->published()->count();
        $pendingProducts = Product::whereHas('inventory', fn($q) => $q->where('supplier_id', $supplierId))->draft()->count();
        $draftProducts = Product::whereHas('inventory', fn($q) => $q->where('supplier_id', $supplierId))->draft()->count();
        $rejectedProducts = 0;
        $paidSettlements = SupplierPayout::bySupplier($supplierId)->completed()->sum('net_amount');
        $pendingSettlements = $balance->pending_balance;

        return view('supplier.dashboard', compact(
            'totalInventory', 'lowStockCount', 'outOfStockCount',
            'totalOrders', 'pendingOrders', 'processingOrders', 'completedOrders', 'cancelledOrders', 'returnedOrders',
            'totalRevenue', 'balance',
            'lowStockItems', 'recentOrders', 'monthlySales', 'topProducts', 'topCategories',
            'recentActivities', 'notifications', 'bestSellingProducts',
            'activeProducts', 'pendingProducts', 'draftProducts', 'rejectedProducts',
            'paidSettlements', 'pendingSettlements'
        ));
    }

    // ─── Profile Management ────────────────────────────────────────────────────

    public function profile(): View
    {
        $profile = auth()->user()->supplierProfile;
        return view('supplier.profile', compact('profile'));
    }

    public function profileUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name'     => ['required', 'string', 'max:255'],
            'brand_name'       => ['nullable', 'string', 'max:255'],
            'company_slug'     => ['nullable', 'string', 'max:255', 'unique:supplier_profiles,company_slug,' . auth()->id() . ',user_id'],
            'description'      => ['nullable', 'string'],
            'address'          => ['nullable', 'string', 'max:500'],
            'warehouse_address' => ['nullable', 'string', 'max:500'],
            'pickup_address'   => ['nullable', 'string', 'max:500'],
            'return_address'   => ['nullable', 'string', 'max:500'],
            'phone'            => ['nullable', 'string', 'max:20'],
            'contact_email'    => ['nullable', 'email', 'max:255'],
            'contact_person'   => ['nullable', 'string', 'max:255'],
            'website'          => ['nullable', 'url', 'max:255'],
            'trade_license'    => ['nullable', 'string', 'max:255'],
            'vat_number'       => ['nullable', 'string', 'max:100'],
            'company_logo'     => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'company_banner'   => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ]);

        $profile = auth()->user()->supplierProfile;

        if ($request->hasFile('company_logo')) {
            if ($profile->company_logo) {
                Storage::disk('public')->delete($profile->company_logo);
            }
            $validated['company_logo'] = $request->file('company_logo')->store('supplier-logos', 'public');
        }

        if ($request->hasFile('company_banner')) {
            if ($profile->company_banner) {
                Storage::disk('public')->delete($profile->company_banner);
            }
            $validated['company_banner'] = $request->file('company_banner')->store('supplier-banners', 'public');
        }

        $profile->update($validated);

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }

    // ─── KYC Verification ──────────────────────────────────────────────────────

    public function kyc(): View
    {
        $profile = auth()->user()->supplierProfile;
        return view('supplier.kyc', compact('profile'));
    }

    public function kycSubmit(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'national_id'            => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:5120'],
            'passport'               => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:5120'],
            'business_license_file'  => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:5120'],
            'tax_certificate'        => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:5120'],
            'company_registration_doc' => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:5120'],
            'bank_verification_doc'  => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:5120'],
            'address_verification_doc' => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:5120'],
        ]);

        $profile = auth()->user()->supplierProfile;
        $paths = [];

        foreach (['national_id', 'passport', 'business_license_file', 'tax_certificate', 'company_registration_doc', 'bank_verification_doc', 'address_verification_doc'] as $field) {
            if ($request->hasFile($field)) {
                if ($profile->$field) {
                    Storage::disk('public')->delete($profile->$field);
                }
                $paths[$field] = $request->file($field)->store('supplier-kyc', 'public');
            }
        }

        if (!empty($paths)) {
            $profile->update(array_merge($paths, ['kyc_status' => 'pending']));
        }

        return redirect()->back()->with('success', 'KYC documents submitted for review.');
    }

    // ─── Inventory Management ──────────────────────────────────────────────────

    public function inventory(): View
    {
        $inventory = Inventory::where('supplier_id', auth()->id())
            ->with('product')->latest()->paginate(15);
        return view('supplier.inventory.index', compact('inventory'));
    }

    public function inventoryEdit(Inventory $inventory): View
    {
        if ($inventory->supplier_id !== auth()->id()) abort(403);
        return view('supplier.inventory.form', compact('inventory'));
    }

    public function inventoryUpdate(Request $request, Inventory $inventory): RedirectResponse
    {
        if ($inventory->supplier_id !== auth()->id()) abort(403);

        $validated = $request->validate([
            'stock_quantity'        => ['required', 'integer', 'min:0'],
            'stock_alert_threshold' => ['required', 'integer', 'min:0'],
            'warehouse_location'    => ['nullable', 'string', 'max:255'],
            'batch_number'          => ['nullable', 'string', 'max:100'],
            'expiry_date'           => ['nullable', 'date'],
        ]);

        $inventory->update($validated);
        return redirect()->back()->with('success', 'Inventory updated successfully.');
    }

    public function inventoryAdjust(Request $request, Inventory $inventory): RedirectResponse
    {
        if ($inventory->supplier_id !== auth()->id()) abort(403);

        $validated = $request->validate([
            'quantity' => ['required', 'integer'],
            'reason'   => ['required', 'string', 'max:500'],
            'type'     => ['required', 'in:adjustment,damage,return,transfer'],
        ]);

        $oldQty = $inventory->stock_quantity;
        $newQty = max(0, $oldQty + $validated['quantity']);
        $inventory->update(['stock_quantity' => $newQty]);

        InventoryLog::create([
            'product_id'      => $inventory->product_id,
            'user_id'         => auth()->id(),
            'type'            => $validated['type'],
            'quantity_change' => $validated['quantity'],
            'previous_stock'  => $oldQty,
            'new_stock'       => $newQty,
            'reason'          => $validated['reason'],
        ]);

        return redirect()->back()->with('success', 'Stock adjusted successfully.');
    }

    public function inventoryLogs(Product $product): View
    {
        if (!Inventory::where('product_id', $product->id)->where('supplier_id', auth()->id())->exists()) {
            abort(403);
        }
        $logs = InventoryLog::where('product_id', $product->id)->with('user')->latest()->paginate(20);
        return view('supplier.inventory.logs', compact('product', 'logs'));
    }

    public function inventoryTransfer(Request $request, Inventory $inventory): RedirectResponse
    {
        if ($inventory->supplier_id !== auth()->id()) abort(403);

        $validated = $request->validate([
            'target_warehouse' => ['required', 'string', 'max:255'],
            'quantity'         => ['required', 'integer', 'min:1', 'max:' . $inventory->stock_quantity],
        ]);

        $oldQty = $inventory->stock_quantity;
        $newQty = $oldQty - $validated['quantity'];
        $inventory->update(['stock_quantity' => $newQty, 'warehouse_location' => $validated['target_warehouse']]);

        InventoryLog::create([
            'product_id'      => $inventory->product_id,
            'user_id'         => auth()->id(),
            'type'            => 'transfer',
            'quantity_change' => -$validated['quantity'],
            'previous_stock'  => $oldQty,
            'new_stock'       => $newQty,
            'reason'          => 'Transferred to ' . $validated['target_warehouse'],
        ]);

        return redirect()->back()->with('success', 'Stock transferred successfully.');
    }

    // ─── Order Management ──────────────────────────────────────────────────────

    public function orders(): View
    {
        $orderIds = OrderItem::where('supplier_id', auth()->id())->distinct()->pluck('order_id');
        $orders = Order::whereIn('id', $orderIds)
            ->with(['items' => fn($q) => $q->where('supplier_id', auth()->id())->with('product')])
            ->latest()->paginate(15);
        $filter = null;
        return view('supplier.orders.index', compact('orders', 'filter'));
    }

    public function orderShow(Order $order): View
    {
        $items = $order->items()->where('supplier_id', auth()->id())->with('product')->get();
        if ($items->isEmpty()) abort(403);
        return view('supplier.orders.show', compact('order', 'items'));
    }

    public function orderFulfill(Request $request, Order $order): RedirectResponse
    {
        if ($order->items()->where('supplier_id', auth()->id())->doesntExist()) abort(403);

        $supplierItems = $order->items()->where('supplier_id', auth()->id())->get();
        foreach ($supplierItems as $item) {
            $item->status = 'shipped';
            $item->save();
        }

        $allItems = $order->items()->pluck('status');
        if ($allItems->every(fn($s) => $s === 'shipped')) {
            $order->status = 'shipped';
            $order->shipped_at = now();
            $order->save();
        }

        return redirect()->back()->with('success', 'Items marked as shipped.');
    }

    public function orderAccept(Order $order): RedirectResponse
    {
        if ($order->items()->where('supplier_id', auth()->id())->doesntExist()) abort(403);
        $order->items()->where('supplier_id', auth()->id())->update(['status' => 'processing']);
        return redirect()->back()->with('success', 'Order accepted.');
    }

    public function orderReject(Request $request, Order $order): RedirectResponse
    {
        if ($order->items()->where('supplier_id', auth()->id())->doesntExist()) abort(403);

        $validated = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $order->items()->where('supplier_id', auth()->id())->update([
            'status' => 'cancelled',
        ]);
        $order->update(['cancellation_reason' => $validated['reason'], 'cancelled_at' => now(), 'status' => 'cancelled']);

        return redirect()->back()->with('success', 'Order rejected.');
    }

    public function orderMarkPacked(Order $order): RedirectResponse
    {
        if ($order->items()->where('supplier_id', auth()->id())->doesntExist()) abort(403);
        $order->items()->where('supplier_id', auth()->id())->update(['status' => 'packed']);
        return redirect()->back()->with('success', 'Items marked as packed.');
    }

    public function orderMarkReady(Order $order): RedirectResponse
    {
        if ($order->items()->where('supplier_id', auth()->id())->doesntExist()) abort(403);
        $order->items()->where('supplier_id', auth()->id())->update(['status' => 'ready_for_pickup']);
        return redirect()->back()->with('success', 'Items ready for pickup.');
    }

    public function orderInvoice(Order $order): View
    {
        if ($order->items()->where('supplier_id', auth()->id())->doesntExist()) abort(403);
        $items = $order->items()->where('supplier_id', auth()->id())->with('product')->get();
        $profile = auth()->user()->supplierProfile;
        return view('supplier.orders.invoice', compact('order', 'items', 'profile'));
    }

    public function orderAddNote(Request $request, Order $order): RedirectResponse
    {
        if ($order->items()->where('supplier_id', auth()->id())->doesntExist()) abort(403);

        $validated = $request->validate(['note' => ['required', 'string', 'max:1000']]);
        $existing = json_decode($order->admin_notes ?? '[]', true);
        $existing[] = ['user' => auth()->user()->name, 'note' => $validated['note'], 'at' => now()->toDateTimeString()];
        $order->update(['admin_notes' => json_encode($existing)]);

        return redirect()->back()->with('success', 'Note added.');
    }

    // ─── Order Filters ─────────────────────────────────────────────────────────

    public function ordersNew(): View
    {
        return $this->filteredOrders('pending');
    }

    public function ordersAccepted(): View
    {
        return $this->filteredOrders('processing');
    }

    public function ordersShipped(): View
    {
        return $this->filteredOrders('shipped');
    }

    public function ordersDelivered(): View
    {
        return $this->filteredOrders('delivered');
    }

    public function ordersReturned(): View
    {
        return $this->filteredOrders('returned');
    }

    public function ordersCancelled(): View
    {
        return $this->filteredOrders('cancelled');
    }

    private function filteredOrders(string $status): View
    {
        $orderIds = OrderItem::where('supplier_id', auth()->id())
            ->where('status', $status)->distinct()->pluck('order_id');
        $orders = Order::whereIn('id', $orderIds)
            ->with(['items' => fn($q) => $q->where('supplier_id', auth()->id())->with('product')])
            ->latest()->paginate(15);
        $filter = $status;
        return view('supplier.orders.index', compact('orders', 'filter'));
    }

    // ─── Shipping Management ───────────────────────────────────────────────────

    public function shippingZones(): View
    {
        $zones = SupplierShippingZone::where('supplier_id', auth()->id())->with('rates')->latest()->get();
        return view('supplier.shipping.zones', compact('zones'));
    }

    public function shippingZonesStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'countries' => ['nullable', 'array'],
            'states'    => ['nullable', 'array'],
            'cities'    => ['nullable', 'array'],
            'zip_codes' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ]);

        $validated['supplier_id'] = auth()->id();
        SupplierShippingZone::create($validated);

        return redirect()->back()->with('success', 'Shipping zone created.');
    }

    public function shippingZonesUpdate(Request $request, SupplierShippingZone $zone): RedirectResponse
    {
        if ($zone->supplier_id !== auth()->id()) abort(403);

        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'countries' => ['nullable', 'array'],
            'states'    => ['nullable', 'array'],
            'cities'    => ['nullable', 'array'],
            'zip_codes' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ]);

        $zone->update($validated);
        return redirect()->back()->with('success', 'Shipping zone updated.');
    }

    public function shippingZonesDestroy(SupplierShippingZone $zone): RedirectResponse
    {
        if ($zone->supplier_id !== auth()->id()) abort(403);
        $zone->rates()->delete();
        $zone->delete();
        return redirect()->back()->with('success', 'Shipping zone deleted.');
    }

    public function shippingRates(SupplierShippingZone $zone): View
    {
        if ($zone->supplier_id !== auth()->id()) abort(403);
        $rates = $zone->rates()->latest()->get();
        return view('supplier.shipping.rates', compact('zone', 'rates'));
    }

    public function shippingRatesStore(Request $request, SupplierShippingZone $zone): RedirectResponse
    {
        if ($zone->supplier_id !== auth()->id()) abort(403);

        $validated = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'carrier'           => ['nullable', 'string', 'max:50'],
            'type'              => ['required', 'in:flat,weight_based,order_total_based,free'],
            'rate'              => ['required', 'numeric', 'min:0'],
            'min_weight'        => ['nullable', 'numeric', 'min:0'],
            'max_weight'        => ['nullable', 'numeric', 'min:0'],
            'min_order_total'   => ['nullable', 'numeric', 'min:0'],
            'max_order_total'   => ['nullable', 'numeric', 'min:0'],
            'estimated_days_min' => ['nullable', 'integer', 'min:0'],
            'estimated_days_max' => ['nullable', 'integer', 'min:0'],
            'is_active'         => ['boolean'],
        ]);

        $validated['supplier_id'] = auth()->id();
        $validated['shipping_zone_id'] = $zone->id;
        SupplierShippingRate::create($validated);

        return redirect()->back()->with('success', 'Shipping rate created.');
    }

    public function shippingRatesUpdate(Request $request, SupplierShippingRate $rate): RedirectResponse
    {
        if ($rate->supplier_id !== auth()->id()) abort(403);

        $validated = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'carrier'           => ['nullable', 'string', 'max:50'],
            'type'              => ['required', 'in:flat,weight_based,order_total_based,free'],
            'rate'              => ['required', 'numeric', 'min:0'],
            'min_weight'        => ['nullable', 'numeric', 'min:0'],
            'max_weight'        => ['nullable', 'numeric', 'min:0'],
            'min_order_total'   => ['nullable', 'numeric', 'min:0'],
            'max_order_total'   => ['nullable', 'numeric', 'min:0'],
            'estimated_days_min' => ['nullable', 'integer', 'min:0'],
            'estimated_days_max' => ['nullable', 'integer', 'min:0'],
            'is_active'         => ['boolean'],
        ]);

        $rate->update($validated);
        return redirect()->back()->with('success', 'Shipping rate updated.');
    }

    public function shippingRatesDestroy(SupplierShippingRate $rate): RedirectResponse
    {
        if ($rate->supplier_id !== auth()->id()) abort(403);
        $rate->delete();
        return redirect()->back()->with('success', 'Shipping rate deleted.');
    }

    // ─── Returns & Refunds ─────────────────────────────────────────────────────

    public function returns(): View
    {
        $returns = ReturnRequest::whereHas('orderItem', fn($q) => $q->where('supplier_id', auth()->id()))
            ->with(['orderItem.product', 'orderItem.order', 'user'])->latest()->paginate(15);
        return view('supplier.returns.index', compact('returns'));
    }

    public function returnShow(ReturnRequest $return): View
    {
        if ($return->orderItem->supplier_id !== auth()->id()) abort(403);
        return view('supplier.returns.show', compact('return'));
    }

    public function returnUpdate(Request $request, ReturnRequest $return): RedirectResponse
    {
        if ($return->orderItem->supplier_id !== auth()->id()) abort(403);

        $validated = $request->validate([
            'status'      => ['required', 'in:approved,rejected,refunded,replaced'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $return->update($validated);

        if ($validated['status'] === 'refunded') {
            $return->orderItem->update(['status' => 'returned']);
        } elseif ($validated['status'] === 'approved') {
            $return->orderItem->update(['status' => 'returned']);
        }

        return redirect()->back()->with('success', 'Return request updated.');
    }

    // ─── Reviews ───────────────────────────────────────────────────────────────

    public function reviews(): View
    {
        $productIds = Inventory::where('supplier_id', auth()->id())->pluck('product_id');
        $reviews = Review::whereIn('product_id', $productIds)->with(['product', 'user'])->latest()->paginate(15);
        return view('supplier.reviews.index', compact('reviews'));
    }

    public function reviewReply(Request $request, Review $review): RedirectResponse
    {
        $productIds = Inventory::where('supplier_id', auth()->id())->pluck('product_id');
        if (!in_array($review->product_id, $productIds->toArray())) abort(403);

        $validated = $request->validate(['reply' => ['required', 'string', 'max:2000']]);

        ReviewReply::updateOrCreate(
            ['review_id' => $review->id, 'seller_id' => auth()->id()],
            ['body' => $validated['reply']]
        );

        return redirect()->back()->with('success', 'Reply submitted.');
    }

    public function reviewReportFake(Review $review): RedirectResponse
    {
        $productIds = Inventory::where('supplier_id', auth()->id())->pluck('product_id');
        if (!in_array($review->product_id, $productIds->toArray())) abort(403);

        app(AuditService::class)->log('review.reported_fake', $review, null, [
            'reported_by' => auth()->id(),
            'review_id'   => $review->id,
        ]);

        return redirect()->back()->with('success', 'Review reported for review.');
    }

    // ─── Messaging ─────────────────────────────────────────────────────────────

    public function messages(): View
    {
        $messages = SupplierMessage::bySupplier(auth()->id())->with('sender')->latest()->paginate(15);
        return view('supplier.messages.index', compact('messages'));
    }

    public function messageShow(SupplierMessage $message): View
    {
        if ($message->supplier_id !== auth()->id()) abort(403);
        $message->update(['is_read' => true, 'read_at' => now()]);
        return view('supplier.messages.show', compact('message'));
    }

    public function messageReply(Request $request, SupplierMessage $message): RedirectResponse
    {
        if ($message->supplier_id !== auth()->id()) abort(403);

        $validated = $request->validate([
            'message'     => ['required', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:jpeg,png,jpg,pdf,doc,docx', 'max:10240'],
        ]);

        $attachmentPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $attachmentPaths[] = $file->store('supplier-messages', 'public');
            }
        }

        SupplierMessageReply::create([
            'supplier_message_id' => $message->id,
            'user_id'             => auth()->id(),
            'message'             => $validated['message'],
            'attachments'         => !empty($attachmentPaths) ? $attachmentPaths : null,
        ]);

        return redirect()->back()->with('success', 'Reply sent.');
    }

    // ─── Wallet & Finance ──────────────────────────────────────────────────────

    public function wallet(): View
    {
        $supplierId = auth()->id();
        $balance = SupplierBalance::firstOrCreate(
            ['supplier_id' => $supplierId],
            ['balance' => 0, 'pending_balance' => 0, 'total_earned' => 0, 'total_withdrawn' => 0, 'platform_fees' => 0]
        );

        $payouts = SupplierPayout::bySupplier($supplierId)->latest()->paginate(15);

        $recentTransactions = Transaction::where('accountable_type', SupplierBalance::class)
            ->whereHas('accountable', fn ($q) => $q->where('supplier_id', $supplierId))
            ->latest()
            ->take(20)
            ->get();

        // Fall back to delivered order items when no ledger entries exist yet
        if ($recentTransactions->isEmpty()) {
            $recentTransactions = OrderItem::where('supplier_id', $supplierId)
                ->whereHas('order', fn ($q) => $q->where('status', 'delivered'))
                ->with('order')
                ->latest()
                ->take(20)
                ->get()
                ->map(fn ($item) => (object) [
                    'created_at' => $item->created_at,
                    'description' => 'Sale: ' . $item->product_name,
                    'amount' => $item->subtotal,
                    'type' => 'credit',
                    'order' => $item->order,
                ]);
        }

        return view('supplier.wallet.index', compact('balance', 'payouts', 'recentTransactions'));
    }

    public function payoutRequest(Request $request): RedirectResponse
    {
        $supplierId = auth()->id();

        $validated = $request->validate([
            'amount'         => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'string', 'in:bank,paypal,stripe,wise,payoneer'],
            'account_details' => ['required', 'array'],
        ]);

        try {
            $payout = DB::transaction(function () use ($supplierId, $validated) {
                $balance = SupplierBalance::where('supplier_id', $supplierId)->lockForUpdate()->first();

                if (!$balance) {
                    throw new \RuntimeException('Supplier balance not found.');
                }

                if ($validated['amount'] > $balance->balance) {
                    throw ValidationException::withMessages([
                        'amount' => 'Requested amount exceeds your available balance.',
                    ]);
                }

                $platformFee = $validated['amount'] * 0.02;
                $tax = $validated['amount'] * 0.01;
                $netAmount = $validated['amount'] - $platformFee - $tax;

                $payout = SupplierPayout::create([
                    'supplier_id'     => $supplierId,
                    'amount'          => $validated['amount'],
                    'platform_fee'    => $platformFee,
                    'tax'             => $tax,
                    'net_amount'      => $netAmount,
                    'payment_method'  => $validated['payment_method'],
                    'account_details' => $validated['account_details'],
                    'status'          => 'pending',
                ]);

                $balance->decrement('balance', $validated['amount']);
                $balance->increment('pending_balance', $validated['amount']);

                Transaction::create([
                    'accountable_type' => SupplierBalance::class,
                    'accountable_id'   => $balance->id,
                    'reference_type'   => SupplierPayout::class,
                    'reference_id'     => $payout->id,
                    'type'             => 'payout',
                    'amount'           => $validated['amount'],
                    'description'      => 'Payout requested',
                    'status'           => 'pending',
                    'method'           => $validated['payment_method'],
                ]);

                return $payout;
            });
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            Log::error('Supplier payout request failed', [
                'supplier_id' => $supplierId,
                'error'       => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Unable to process payout request. Please try again.');
        }

        return redirect()->back()->with('success', 'Payout request submitted.');
    }

    // ─── Settlements History ───────────────────────────────────────────────────

    public function settlements(): View
    {
        $supplierId = auth()->id();
        $payouts = SupplierPayout::bySupplier($supplierId)->latest()->paginate(15);
        $balance = SupplierBalance::firstOrCreate(['supplier_id' => $supplierId]);
        return view('supplier.finance.settlements', compact('payouts', 'balance'));
    }

    // ─── Notifications ─────────────────────────────────────────────────────────

    public function notifications(): View
    {
        $notifications = UserNotification::where('notifiable_id', auth()->id())
            ->where('notifiable_type', 'App\Models\User')->latest()->paginate(20);
        return view('supplier.notifications.index', compact('notifications'));
    }

    public function markNotificationRead(UserNotification $notification): RedirectResponse
    {
        if ((string) $notification->notifiable_id !== (string) auth()->id()) abort(403);
        $notification->update(['read_at' => now()]);
        return redirect()->back();
    }

    public function markAllNotificationsRead(): RedirectResponse
    {
        UserNotification::where('notifiable_id', auth()->id())
            ->where('notifiable_type', 'App\Models\User')->unread()->update(['read_at' => now()]);
        return redirect()->back()->with('success', 'All notifications marked as read.');
    }

    // ─── Reports & Analytics ───────────────────────────────────────────────────

    public function reports(): View
    {
        return view('supplier.reports.index');
    }

    public function reportGenerate(string $type): View
    {
        $supplierId = auth()->id();
        $from = request('from', now()->subMonth()->toDateString());
        $to = request('to', now()->toDateString());

        $data = match ($type) {
            'sales' => OrderItem::where('supplier_id', $supplierId)
                ->whereBetween('created_at', [$from, $to])
                ->with('order')->paginate(20),
            'inventory' => Inventory::where('supplier_id', $supplierId)
                ->with('product')->paginate(20),
            'orders' => Order::whereIn('id', OrderItem::where('supplier_id', $supplierId)->distinct()->pluck('order_id'))
                ->whereBetween('created_at', [$from, $to])->with('items')->paginate(20),
            'products' => OrderItem::where('supplier_id', $supplierId)
                ->select('product_id', 'product_name', 'sku', DB::raw('SUM(quantity) as total_sold'), DB::raw('SUM(subtotal) as total_revenue'))
                ->groupBy('product_id', 'product_name', 'sku')->orderByDesc('total_sold')->paginate(20),
            'settlements' => SupplierPayout::bySupplier($supplierId)
                ->whereBetween('created_at', [$from, $to])->paginate(20),
            'revenue' => OrderItem::where('supplier_id', $supplierId)
                ->whereHas('order', fn($q) => $q->where('status', 'delivered'))
                ->whereBetween('created_at', [$from, $to])
                ->select(DB::raw(self::monthExpression().' as month'), DB::raw('SUM(subtotal) as total'), DB::raw('COUNT(DISTINCT order_id) as count'))
                ->groupBy('month')->paginate(20),
            default => collect([]),
        };

        return view('supplier.reports.generate', compact('type', 'data', 'from', 'to'));
    }

    public function exportReport(Request $request): StreamedResponse
    {
        $supplierId = auth()->id();
        $validated = $request->validate([
            'type' => ['required', 'in:sales,revenue,inventory,orders,products,settlements,tax'],
            'from' => ['nullable', 'date'],
            'to'   => ['nullable', 'date', 'after_or_equal:from'],
            'format' => ['required', 'in:csv,pdf'],
        ]);

        $from = $validated['from'] ?? now()->subMonth();
        $to = $validated['to'] ?? now();
        $filename = 'supplier-' . $validated['type'] . '-report-' . now()->format('Y-m-d') . '.csv';

        $headers = match ($validated['type']) {
            'sales' => ['Date', 'Order', 'Product', 'Quantity', 'Unit Price', 'Subtotal', 'Commission', 'Net'],
            'revenue' => ['Month', 'Total Revenue', 'Platform Fees', 'Tax', 'Net Revenue', 'Order Count'],
            'inventory' => ['Product', 'SKU', 'Stock', 'Threshold', 'Warehouse', 'Status'],
            'orders' => ['Order #', 'Date', 'Status', 'Items', 'Subtotal', 'Shipping', 'Total'],
            'products' => ['Product Name', 'SKU', 'Total Sold', 'Revenue', 'Category'],
            'settlements' => ['Date', 'Amount', 'Fee', 'Tax', 'Net', 'Method', 'Status'],
            'tax' => ['Date', 'Order #', 'Amount', 'Tax Rate', 'Tax Amount'],
            default => ['Date', 'Amount'],
        };

        $callback = function () use ($validated, $supplierId, $from, $to, $headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);

            $rows = match ($validated['type']) {
                'sales' => OrderItem::where('supplier_id', $supplierId)
                    ->whereBetween('created_at', [$from, $to])
                    ->with('order')->get()
                    ->map(fn($i) => [$i->created_at->format('Y-m-d'), $i->order?->order_number, $i->product_name, $i->quantity, number_format($i->unit_price, 2), number_format($i->subtotal, 2), '0.00', number_format($i->subtotal, 2)]),
                'revenue' => OrderItem::where('supplier_id', $supplierId)
                    ->whereHas('order', fn($q) => $q->where('status', 'delivered'))
                    ->whereBetween('created_at', [$from, $to])
                    ->select(DB::raw(self::monthExpression().' as month'), DB::raw('SUM(subtotal) as total'), DB::raw('COUNT(DISTINCT order_id) as count'))
                    ->groupBy('month')->get()
                    ->map(fn($r) => [$r->month, number_format($r->total, 2), '0.00', '0.00', number_format($r->total * 0.97, 2), $r->count]),
                'inventory' => Inventory::where('supplier_id', $supplierId)->with('product')->get()
                    ->map(fn($i) => [$i->product?->name ?? 'N/A', $i->product?->sku ?? 'N/A', $i->stock_quantity, $i->stock_alert_threshold, $i->warehouse_location ?? 'N/A', $i->is_low_stock ? 'Low' : ($i->is_out_of_stock ? 'Out' : 'In Stock')]),
                'orders' => Order::whereIn('id', OrderItem::where('supplier_id', $supplierId)->distinct()->pluck('order_id'))
                    ->whereBetween('created_at', [$from, $to])->with('items')->get()
                    ->map(fn($o) => [$o->order_number, $o->created_at->format('Y-m-d'), $o->status, $o->items->where('supplier_id', auth()->id())->sum('quantity'), number_format($o->items->where('supplier_id', auth()->id())->sum('subtotal'), 2), number_format($o->shipping_cost, 2), number_format($o->total, 2)]),
                'products' => OrderItem::where('supplier_id', $supplierId)
                    ->select('product_id', 'product_name', 'sku', DB::raw('SUM(quantity) as total_sold'), DB::raw('SUM(subtotal) as total_revenue'))
                    ->groupBy('product_id', 'product_name', 'sku')->orderByDesc('total_sold')->get()
                    ->map(fn($p) => [$p->product_name, $p->sku ?? 'N/A', $p->total_sold, number_format($p->total_revenue, 2), '']),
                'settlements' => SupplierPayout::bySupplier($supplierId)
                    ->whereBetween('created_at', [$from, $to])->get()
                    ->map(fn($p) => [$p->created_at->format('Y-m-d'), number_format($p->amount, 2), number_format($p->platform_fee, 2), number_format($p->tax, 2), number_format($p->net_amount, 2), $p->payment_method, $p->status]),
                'tax' => [],
                default => [],
            };

            foreach ($rows as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // ─── Settings ──────────────────────────────────────────────────────────────

    public function settings(): View
    {
        $profile = auth()->user()->supplierProfile;
        return view('supplier.settings.index', compact('profile'));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $profile = auth()->user()->supplierProfile;

        $validated = $request->validate([
            'working_hours'       => ['nullable', 'array'],
            'holiday_calendar'    => ['nullable', 'array'],
            'shipping_preferences' => ['nullable', 'array'],
            'payment_settings'    => ['nullable', 'array'],
            'bank_account'        => ['nullable', 'array'],
            'notification_email'  => ['nullable', 'email', 'max:255'],
            'language'            => ['nullable', 'string', 'max:10'],
            'timezone'            => ['nullable', 'string', 'max:50'],
            'currency'            => ['nullable', 'string', 'max:10'],
        ]);

        $profile->update($validated);

        return redirect()->back()->with('success', 'Settings updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'string', 'min:8', 'confirmed', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[0-9]/', 'regex:/[@$!%*#?&]/'],
        ]);

        auth()->user()->update(['password' => Hash::make($validated['password'])]);

        return redirect()->back()->with('success', 'Password updated.');
    }

    // ─── Customer Management ───────────────────────────────────────────────────

    public function customers(): View
    {
        $supplierId = auth()->id();
        $customerIds = OrderItem::where('supplier_id', $supplierId)
            ->distinct()->pluck('order_id');
        $customers = \App\Models\User::whereIn('id', function ($q) use ($customerIds) {
            $q->select('user_id')->from('orders')->whereIn('id', $customerIds);
        })->paginate(20);

        return view('supplier.customers.index', compact('customers'));
    }

    public function customerShow(\App\Models\User $customer): View
    {
        $supplierId = auth()->id();
        $orders = Order::where('user_id', $customer->id)
            ->whereIn('id', OrderItem::where('supplier_id', $supplierId)->distinct()->pluck('order_id'))
            ->with(['items' => fn($q) => $q->where('supplier_id', $supplierId)->with('product')])
            ->latest()->paginate(15);

        $totalSpent = OrderItem::where('supplier_id', $supplierId)
            ->whereIn('order_id', $orders->pluck('id'))->sum('subtotal');

        return view('supplier.customers.show', compact('customer', 'orders', 'totalSpent'));
    }

    // ─── KYC Status (API helper) ──────────────────────────────────────────────

    public function kycStatus(): JsonResponse
    {
        $profile = auth()->user()->supplierProfile;
        return response()->json([
            'status'  => $profile->kyc_status,
            'label'   => $profile->kyc_status_label,
            'is_complete' => $profile->kyc_completed,
        ]);
    }

    // ─── Become Supplier ──────────────────────────────────────────────────────

    public function becomeSupplier(AuditService $auditService): RedirectResponse
    {
        $user = auth()->user();

        if ($user->isSupplier()) {
            return redirect()->route('supplier.dashboard');
        }

        if ($user->isAdmin()) {
            return redirect()->back()->with('error', 'Admins cannot become suppliers.');
        }

        $oldRole = $user->role_type;

        SupplierProfile::create([
            'user_id'      => $user->id,
            'company_name' => $user->name . "'s Company",
            'status'       => 'pending',
        ]);

        $user->role_type = 'supplier';
        $user->save();

        $auditService->log('role.change', $user, ['role_type' => $oldRole], ['role_type' => 'supplier']);

        return redirect()->route('supplier.dashboard')
            ->with('success', 'Welcome! Your supplier account is pending approval.');
    }
}
