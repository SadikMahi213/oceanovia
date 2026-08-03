<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Commission;
use App\Models\Category;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function sales(Request $request): View
    {
        $start = $request->date('start_date') ?: now()->startOfMonth();
        $end = $request->date('end_date') ?: now()->endOfDay();

        $orders = Order::whereBetween('created_at', [$start, $end])
            ->whereNotIn('status', ['cancelled', 'refunded']);

        $stats = (object) [
            'total_revenue' => (clone $orders)->sum('total'),
            'total_orders' => (clone $orders)->count(),
            'avg_order_value' => (clone $orders)->avg('total') ?? 0,
            'total_commission' => Commission::whereHas('orderItem', function ($q) use ($start, $end) {
                $q->whereHas('order', function ($q2) use ($start, $end) {
                    $q2->whereBetween('created_at', [$start, $end])
                        ->whereNotIn('status', ['cancelled', 'refunded']);
                });
            })->sum('amount'),
        ];

        $dailySales = Order::whereBetween('created_at', [$start, $end])
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total) as revenue'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('admin.reports.sales', compact('stats', 'dailySales', 'start', 'end'));
    }

    public function sellers(Request $request): View
    {
        $search = $request->input('search');

        $sellers = User::ofType('seller')
            ->when($search, fn ($q) => $q->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->with('sellerProfile')
            ->withCount('products')
            ->paginate(15);

        $sellers->getCollection()->transform(function ($seller) {
            $revenue = OrderItem::where('seller_id', $seller->id)
                ->whereHas('order', fn ($q) => $q->whereNotIn('status', ['cancelled', 'refunded']))
                ->sum('subtotal');

            $orderCount = OrderItem::where('seller_id', $seller->id)
                ->whereHas('order', fn ($q) => $q->whereNotIn('status', ['cancelled', 'refunded']))
                ->distinct('order_id')
                ->count('order_id');

            $commission = Commission::bySeller($seller->id)->sum('amount');

            $seller->total_revenue = $revenue;
            $seller->total_orders = $orderCount;
            $seller->commission_earned = $commission;

            return $seller;
        });

        return view('admin.reports.sellers', compact('sellers'));
    }

    public function products(Request $request): View
    {
        $categoryId = $request->input('category_id');

        $products = Product::published()
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->with(['seller', 'category', 'reviews'])
            ->withCount(['orderItems as total_sold' => function ($q) {
                $q->whereHas('order', fn ($q2) => $q2->whereNotIn('status', ['cancelled', 'refunded']));
            }])
            ->orderByDesc('total_sold')
            ->paginate(15);

        $products->getCollection()->transform(function ($product) {
            $product->revenue = $product->orderItems()
                ->whereHas('order', fn ($q) => $q->whereNotIn('status', ['cancelled', 'refunded']))
                ->sum('subtotal');

            $product->avg_rating = Review::forProduct($product->id)->approved()->avg('rating') ?? 0;

            return $product;
        });

        $categories = Category::active()->ordered()->get();

        return view('admin.reports.products', compact('products', 'categories', 'categoryId'));
    }

    public function orders(): View
    {
        $statusBreakdown = Order::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->orderByDesc('count')
            ->get();

        $paymentMethods = Order::select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as total'))
            ->groupBy('payment_method')
            ->orderByDesc('count')
            ->get();

        $dailyOrders = Order::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('admin.reports.orders', compact('statusBreakdown', 'paymentMethods', 'dailyOrders'));
    }
}
