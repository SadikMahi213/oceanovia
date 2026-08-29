<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\RecentlyViewed;
use App\Models\Refund;
use App\Models\ReturnRequest;
use App\Models\Review;
use App\Models\UserNotification;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CustomerController extends Controller
{
    // ─── Dashboard ─────────────────────────────────────────────────────────────

    public function dashboard(): View
    {
        $user = auth()->user();
        $userId = $user->id;

        $ordersCount = Order::byUser($userId)->count();
        $activeOrders = Order::byUser($userId)->whereIn('status', ['pending', 'confirmed', 'processing'])->count();
        $pendingOrders = Order::byUser($userId)->where('status', 'pending')->count();
        $deliveredOrders = Order::byUser($userId)->where('status', 'delivered')->count();
        $cancelledOrders = Order::byUser($userId)->where('status', 'cancelled')->count();
        $returnRequests = ReturnRequest::where('user_id', $userId)->count();

        $wishlistCount = Wishlist::where('user_id', $userId)->count();

        $cart = Cart::with('items')->where('user_id', $userId)->first();
        $cartCount = $cart?->items_count ?? 0;

        $recentOrders = Order::byUser($userId)->with('items')->latest()->take(5)->get();

        $recentlyViewed = RecentlyViewed::forUser($userId)
            ->with('product')
            ->whereHas('product', fn ($q) => $q->published())
            ->orderBy('updated_at', 'desc')
            ->take(8)
            ->get();

        $notifications = UserNotification::where('notifiable_id', $userId)
            ->where('notifiable_type', 'App\Models\User')->unread()->latest()->take(5)->get();
        $unreadNotifications = $notifications->count();

        $wishlistProductIds = Wishlist::where('user_id', $userId)->pluck('product_id');
        $recommendedProducts = Product::published()
            ->whereNotIn('id', $wishlistProductIds)
            ->where('is_featured', true)
            ->inRandomOrder()->take(6)->get();

        $couponCount = Coupon::where('is_active', true)
            ->where(function ($q) { $q->whereNull('expires_at')->orWhere('expires_at', '>', now()); })
            ->where(function ($q) { $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()); })
            ->count();

        return view('customer.dashboard', compact(
            'user', 'ordersCount', 'activeOrders', 'pendingOrders',
            'deliveredOrders', 'cancelledOrders', 'returnRequests',
            'wishlistCount', 'cartCount',
            'recentOrders', 'recentlyViewed', 'notifications', 'unreadNotifications',
            'recommendedProducts', 'couponCount'
        ));
    }

    // ─── Profile ───────────────────────────────────────────────────────────────

    public function profile(): View
    {
        return view('customer.profile', ['user' => auth()->user()]);
    }

    public function profileUpdate(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'lastname'    => ['nullable', 'string', 'max:255'],
            'username'    => ['nullable', 'string', 'max:255', 'unique:users,username,' . $user->id],
            'phone'       => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date'],
            'gender'      => ['nullable', 'string', 'in:male,female,other'],
            'country'     => ['nullable', 'string', 'max:100'],
            'city'        => ['nullable', 'string', 'max:100'],
            'state'       => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'avatar'      => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) Storage::disk('public')->delete($user->avatar);
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        if ($request->hasFile('cover_image')) {
            if ($user->cover_image) Storage::disk('public')->delete($user->cover_image);
            $validated['cover_image'] = $request->file('cover_image')->store('covers', 'public');
        }

        $user->update($validated);

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'string', Rules\Password::min(8)->mixedCase()->numbers()->symbols(), 'confirmed'],
        ]);

        auth()->user()->update(['password' => Hash::make($validated['password'])]);

        return redirect()->back()->with('success', 'Password updated successfully.');
    }

    // ─── Address Book ──────────────────────────────────────────────────────────

    public function addresses(): View
    {
        $addresses = Address::where('user_id', auth()->id())->latest()->get();
        return view('customer.addresses.index', compact('addresses'));
    }

    public function addressStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'address_type'  => ['required', 'in:shipping,billing'],
            'first_name'    => ['required', 'string', 'max:255'],
            'last_name'     => ['nullable', 'string', 'max:255'],
            'phone'         => ['required', 'string', 'max:30'],
            'address_line1' => ['required', 'string', 'max:500'],
            'address_line2' => ['nullable', 'string', 'max:500'],
            'city'          => ['required', 'string', 'max:100'],
            'state'         => ['nullable', 'string', 'max:100'],
            'zip'           => ['nullable', 'string', 'max:20'],
            'country'       => ['required', 'string', 'max:100'],
            'is_default'    => ['boolean'],
        ]);

        $validated['user_id'] = auth()->id();

        if ($validated['is_default'] ?? false) {
            Address::where('user_id', auth()->id())->update(['is_default' => false]);
        }

        Address::create($validated);

        return redirect()->back()->with('success', 'Address added successfully.');
    }

    public function addressUpdate(Request $request, Address $address): RedirectResponse
    {
        if ($address->user_id !== auth()->id()) abort(403);

        $validated = $request->validate([
            'address_type'  => ['required', 'in:shipping,billing'],
            'first_name'    => ['required', 'string', 'max:255'],
            'last_name'     => ['nullable', 'string', 'max:255'],
            'phone'         => ['required', 'string', 'max:30'],
            'address_line1' => ['required', 'string', 'max:500'],
            'address_line2' => ['nullable', 'string', 'max:500'],
            'city'          => ['required', 'string', 'max:100'],
            'state'         => ['nullable', 'string', 'max:100'],
            'zip'           => ['nullable', 'string', 'max:20'],
            'country'       => ['required', 'string', 'max:100'],
            'is_default'    => ['boolean'],
        ]);

        if ($validated['is_default'] ?? false) {
            Address::where('user_id', auth()->id())->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $address->update($validated);

        return redirect()->back()->with('success', 'Address updated successfully.');
    }

    public function addressDestroy(Address $address): RedirectResponse
    {
        if ($address->user_id !== auth()->id()) abort(403);
        $address->delete();
        return redirect()->back()->with('success', 'Address deleted successfully.');
    }

    // ─── Orders ────────────────────────────────────────────────────────────────

    public function orders(): View
    {
        $orders = Order::byUser(auth()->id())->with('items')->latest()->paginate(15);
        $statusCounts = [
            'all' => Order::byUser(auth()->id())->count(),
            'pending' => Order::byUser(auth()->id())->where('status', 'pending')->count(),
            'processing' => Order::byUser(auth()->id())->where('status', 'processing')->count(),
            'shipped' => Order::byUser(auth()->id())->where('status', 'shipped')->count(),
            'delivered' => Order::byUser(auth()->id())->where('status', 'delivered')->count(),
            'cancelled' => Order::byUser(auth()->id())->where('status', 'cancelled')->count(),
        ];
        return view('customer.orders.index', compact('orders', 'statusCounts'));
    }

    public function ordersFilter(string $status): View
    {
        $orders = Order::byUser(auth()->id())->where('status', $status)->with('items')->latest()->paginate(15);
        $statusCounts = [
            'all' => Order::byUser(auth()->id())->count(),
            'pending' => Order::byUser(auth()->id())->where('status', 'pending')->count(),
            'processing' => Order::byUser(auth()->id())->where('status', 'processing')->count(),
            'shipped' => Order::byUser(auth()->id())->where('status', 'shipped')->count(),
            'delivered' => Order::byUser(auth()->id())->where('status', 'delivered')->count(),
            'cancelled' => Order::byUser(auth()->id())->where('status', 'cancelled')->count(),
        ];
        return view('customer.orders.index', compact('orders', 'statusCounts', 'status'));
    }

    public function orderShow(Order $order): View
    {
        if ($order->user_id !== auth()->id()) abort(403);
        $order->load(['items.product', 'shippingAddress', 'billingAddress']);
        return view('customer.orders.show', compact('order'));
    }

    public function orderCancel(Request $request, Order $order): RedirectResponse
    {
        if ($order->user_id !== auth()->id()) abort(403);
        if (!in_array($order->status, ['pending', 'confirmed'])) {
            return redirect()->back()->with('error', 'Order cannot be cancelled.');
        }

        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $order->update(['status' => 'cancelled', 'cancelled_at' => now(), 'cancellation_reason' => $validated['reason'] ?? null]);
        $order->items()->update(['status' => 'cancelled']);

        return redirect()->back()->with('success', 'Order cancelled successfully.');
    }

    public function orderReorder(Order $order): RedirectResponse
    {
        if ($order->user_id !== auth()->id()) abort(403);

        $cart = Cart::firstOrCreate(['user_id' => auth()->id()], ['session_id' => null]);

        foreach ($order->items as $item) {
            $product = Product::published()->find($item->product_id);
            if (!$product) continue;

            $existing = $cart->items()->where('product_id', $product->id)->first();
            if ($existing) {
                $existing->increment('quantity', $item->quantity);
            } else {
                $cart->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $item->quantity,
                    'unit_price' => $product->price,
                ]);
            }
        }

        return redirect()->route('cart.index')->with('success', 'Items added to cart.');
    }

    public function orderInvoice(Order $order)
    {
        if ($order->user_id !== auth()->id()) abort(403);
        $order->load(['items.product', 'shippingAddress']);
        return view('customer.orders.invoice', compact('order'));
    }

    // ─── Wishlist ──────────────────────────────────────────────────────────────

    public function wishlist(): View
    {
        $productIds = Wishlist::where('user_id', auth()->id())->latest()->pluck('product_id');
        $products = Product::published()->whereIn('id', $productIds)
            ->with('category', 'inventory')
            ->get()
            ->sortBy(fn($p) => $productIds->search($p->id));

        return view('customer.wishlist.index', compact('products'));
    }

    // ─── Notifications ─────────────────────────────────────────────────────────

    public function notifications(): View
    {
        $notifications = UserNotification::where('notifiable_id', auth()->id())
            ->where('notifiable_type', 'App\Models\User')
            ->latest()->paginate(20);
        return view('customer.notifications.index', compact('notifications'));
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

    // ─── Reviews ───────────────────────────────────────────────────────────────

    public function reviews(): View
    {
        $reviews = Review::where('user_id', auth()->id())->with('product')->latest()->paginate(15);
        return view('customer.reviews.index', compact('reviews'));
    }

    public function reviewUpdate(Request $request, Review $review): RedirectResponse
    {
        if ($review->user_id !== auth()->id()) abort(403);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title'  => ['nullable', 'string', 'max:255'],
            'body'   => ['nullable', 'string', 'max:5000'],
        ]);

        $review->update($validated);

        return redirect()->back()->with('success', 'Review updated.');
    }

    public function reviewDestroy(Review $review): RedirectResponse
    {
        if ($review->user_id !== auth()->id()) abort(403);
        $review->delete();
        return redirect()->back()->with('success', 'Review deleted.');
    }

    // ─── Coupons ───────────────────────────────────────────────────────────────

    public function coupons(): View
    {
        $available = Coupon::where('is_active', true)
            ->where(function ($q) { $q->whereNull('expires_at')->orWhere('expires_at', '>', now()); })
            ->where(function ($q) { $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()); })
            ->get();

        $usedIds = CouponUsage::where('user_id', auth()->id())->pluck('coupon_id');
        $used = Coupon::whereIn('id', $usedIds)->get();
        $expired = Coupon::where('expires_at', '<', now())->get();

        return view('customer.coupons.index', compact('available', 'used', 'expired'));
    }

    // ─── Saved Items / Recently Viewed ────────────────────────────────────────

    public function recentlyViewed(): View
    {
        $items = RecentlyViewed::forUser(auth()->id())
            ->with('product')
            ->whereHas('product', fn ($q) => $q->published())
            ->orderBy('updated_at', 'desc')
            ->paginate(20);
        return view('customer.saved.recently-viewed', compact('items'));
    }

    // ─── Settings ──────────────────────────────────────────────────────────────

    public function settings(): View
    {
        return view('customer.settings.index', ['user' => auth()->user()]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'notification_preferences' => ['nullable', 'array'],
            'language'                 => ['nullable', 'string', 'max:10'],
            'timezone'                 => ['nullable', 'string', 'max:50'],
        ]);

        auth()->user()->update($validated);

        return redirect()->back()->with('success', 'Settings updated.');
    }

    // ─── Security ──────────────────────────────────────────────────────────────

    public function security(): View
    {
        return view('customer.security.index', ['user' => auth()->user()]);
    }

    // ─── Wallet ────────────────────────────────────────────────────────────────

    public function wallet()
    {
        $userId = auth()->id();

        $refunds = Refund::where('user_id', $userId)->latest()->paginate(15);

        $recentOrders = Order::byUser($userId)->where('status', '!=', 'cancelled')
            ->latest()->take(10)->get();

        return view('customer.wallet.index', compact('refunds', 'recentOrders'));
    }
}
