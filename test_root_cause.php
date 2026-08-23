<?php

// Bootstrap Laravel in testing mode
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Commission;
use App\Models\OrderItem;
use App\Models\SellerBalance;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

echo "=== DATABASE CONNECTION ===\n";
$connection = DB::connection()->getName();
echo 'Database connection: '.$connection."\n\n";

echo "=== TEST 1: sum() on empty collection ===\n";
// Use the testing database (sqlite in memory for tests)
$emptyCommissions = Commission::where('seller_id', 0)->pending();
$result1 = $emptyCommissions->sum('amount');
echo 'sum(amount) on empty commissions: '.var_export($result1, true)."\n";
echo 'sum(amount) ?? 0: '.var_export($result1 ?? 0, true)."\n\n";

echo "=== TEST 3: Check seller users ===\n";
$sellers = User::where('role_type', 'seller')->get();
echo 'Seller users found: '.$sellers->count()."\n\n";

foreach ($sellers as $user) {
    echo 'Seller ID: '.$user->id.', Email: '.$user->email."\n";

    // Test pending commissions for this seller
    $pending = Commission::bySeller($user->id)->pending();
    $pendingCount = $pending->count();
    $sumAmount = $pending->sum('amount');
    echo 'Pending commissions count: '.$pendingCount."\n";
    echo 'sum(amount): '.var_export($sumAmount, true)."\n";
    echo 'sum(amount) ?? 0: '.var_export($sumAmount ?? 0, true)."\n\n";

    // Test total revenue calculation
    $totalRevenue = OrderItem::where('seller_id', $user->id)
        ->whereHas('order', function ($q) {
            $q->where('status', '!=', 'cancelled');
        })
        ->sum('subtotal');
    echo 'sum(subtotal) for totalRevenue: '.var_export($totalRevenue, true)."\n";
    echo 'sum(subtotal) ?? 0: '.var_export($totalRevenue ?? 0, true)."\n\n";

    // Test profile exists
    $profile = $user->sellerProfile;
    echo 'Has sellerProfile: '.($profile ? 'YES' : 'NO')."\n";
    if ($profile) {
        echo 'profile store_name: '.$profile->store_name."\n";
    }
    echo "\n---\n";
}

// Test 4: Check what happens when we call dashboard queries
echo "=== TEST 4: Dashboard query simulation ===\n";
foreach ($sellers as $user) {
    $sellerId = $user->id;

    // Simulate the exact dashboard queries
    $totalProducts = Product::where('seller_id', $sellerId)->count();
    $totalOrders = OrderItem::where('seller_id', $sellerId)
        ->distinct('order_id')
        ->count('order_id');
    $totalRevenue = OrderItem::where('seller_id', $sellerId)
        ->whereHas('order', function ($q) {
            $q->where('status', '!=', 'cancelled');
        })
        ->sum('subtotal');
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
        ->whereHas('inventory', function ($q) {
            $q->lowStock();
        })
        ->with('inventory')
        ->get();
    $outOfStockProducts = Inventory::whereHas('product', function ($q) use ($sellerId) {
        $q->where('seller_id', $sellerId);
    })
        ->where('stock_quantity', 0)
        ->with('product')
        ->count();
    $bestSellingProducts = Product::where('seller_id', $sellerId)
        ->where('total_sold', '>', 0)
        ->orderByDesc('total_sold')
        ->take(5)
        ->get();
    $totalEarnings = SellerBalance::bySeller($sellerId)->value('balance') ?? 0;
    $pendingBalance = Commission::bySeller($sellerId)->pending()->sum('amount');
    $profile = $user->sellerProfile;

    echo "Seller ID: $sellerId\n";
    echo '  totalProducts: '.$totalProducts."\n";
    echo '  totalOrders: '.$totalOrders."\n";
    echo '  totalRevenue: '.var_export($totalRevenue, true).' (null? '.(is_null($totalRevenue) ? 'YES' : 'NO').")\n";
    echo '  totalRevenue ?? 0: '.var_export($totalRevenue ?? 0, true)."\n";
    echo '  pendingOrders: '.$pendingOrders."\n";
    echo '  processingOrders: '.$processingOrders."\n";
    echo '  shippedOrders: '.$shippedOrders."\n";
    echo '  deliveredOrders: '.$deliveredOrders."\n";
    echo '  cancelledOrders: '.$cancelledOrders."\n";
    echo '  lowStockProducts count: '.$lowStockProducts->count()."\n";
    echo '  outOfStockProducts: '.$outOfStockProducts."\n";
    echo '  bestSellingProducts count: '.$bestSellingProducts->count()."\n";
    echo '  totalEarnings: '.$totalEarnings."\n";
    echo '  pendingBalance: '.var_export($pendingBalance, true).' (null? '.(is_null($pendingBalance) ? 'YES' : 'NO').")\n";
    echo '  pendingBalance ?? 0: '.var_export($pendingBalance ?? 0, true)."\n";
    echo '  profile store_name: '.($profile ? $profile->store_name : 'N/A')."\n\n";
}
