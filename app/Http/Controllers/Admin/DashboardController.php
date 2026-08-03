<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SellerPayout;
use App\Models\Refund;
use App\Models\Review;
use App\Models\Product;
use App\Models\User;
use App\Models\Coupon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        // Stats
        $totalOrders = Order::count();
        $pendingOrders = Order::pending()->count();
        $processingOrders = Order::processing()->count();
        $completedOrders = Order::delivered()->count();
        $cancelledOrders = Order::cancelled()->count();
        $totalRevenue = Order::where('payment_status', 'paid')->sum('total');

        // Payouts
        $pendingPayouts = SellerPayout::where('status', 'pending')->sum('amount');
        $pendingPayoutsCount = SellerPayout::where('status', 'pending')->count();

        // Refunds
        $pendingRefundsCount = Refund::where('status', 'pending')->count();
        $totalRefunds = Refund::count();

        // Products
        $totalProducts = Product::count();
        $publishedProducts = Product::published()->count();
        $outOfStockProductsCount = Product::whereHas('inventory', fn($q) => $q->where('stock_quantity', 0))->count();
        $pendingReviews = Review::whereNull('is_approved')->count();

        // Users
        $totalUsers = User::count();
        $totalSellers = User::ofType('seller')->count();
        $totalSuppliers = User::ofType('supplier')->count();

        // Active coupons
        $activeCoupons = Coupon::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->count();

        // Recent items
        $recentOrders = Order::with('user')->latest()->take(8)->get();
        $recentRefunds = Refund::with('user', 'order')->latest()->take(5)->get();
        $recentReviews = Review::with('user', 'product')->latest()->take(5)->get();

        // Top sellers
        $topSellers = SellerPayout::selectRaw('seller_id, SUM(amount) as total_paid')
            ->where('status', 'completed')
            ->groupBy('seller_id')
            ->with('seller')
            ->orderByDesc('total_paid')
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'totalOrders',
            'pendingOrders',
            'processingOrders',
            'completedOrders',
            'cancelledOrders',
            'totalRevenue',
            'pendingPayouts',
            'pendingPayoutsCount',
            'pendingRefundsCount',
            'totalRefunds',
            'totalProducts',
            'publishedProducts',
            'outOfStockProductsCount',
            'pendingReviews',
            'totalUsers',
            'totalSellers',
            'totalSuppliers',
            'activeCoupons',
            'recentOrders',
            'recentRefunds',
            'recentReviews',
            'topSellers',
        ));
    }
}
