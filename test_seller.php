<?php

use App\Models\Commission;
use App\Models\OrderItem;
use App\Models\SellerBalance;
use App\Models\User;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Application;

// Bootstrap Laravel application
$app = require __DIR__.'/vendor/autoload.php';
$app = new Application(dirname(__DIR__));
$app->singleton(Kernel::class, Illuminate\Foundation\Http\Kernel::class);
$app->singleton(Illuminate\Contracts\Console\Kernel::class, Illuminate\Foundation\Console\Kernel::class);

// Bootstrap the application
$app->make('Illuminate\Contracts\Debug\ExceptionHandler')->report();

// Now test
echo "Bootstrap done\n";

// Test 1: Check seller count
$sellerCount = User::where('role_type', 'seller')->count();
echo "Seller count: $sellerCount\n";

// Test 2: Check pending commissions sum for the seller
$seller = User::where('role_type', 'seller')->first();
if ($seller) {
    echo 'Seller ID: '.$seller->id."\n";
    echo 'Seller email: '.$seller->email."\n";

    // Check pending commissions
    $pendingCommissions = Commission::bySeller($seller->id)->pending();
    $pendingCount = $pendingCommissions->count();
    $pendingSum = $pendingCommissions->sum('amount');
    echo "Pending commissions count: $pendingCount\n";
    echo 'Pending commissions sum(amount): '.var_export($pendingSum, true)."\n";
    echo 'Pending commissions sum(amount) ?? 0: '.var_export($pendingSum ?? 0, true)."\n";

    // Check seller profile
    $profile = $seller->sellerProfile;
    echo 'Has sellerProfile: '.($profile ? 'YES' : 'NO')."\n";
    if ($profile) {
        echo 'Profile store_name: '.$profile->store_name."\n";
    }

    // Check seller balance
    $balance = SellerBalance::bySeller($seller->id)->value('balance');
    echo 'Seller balance value: '.var_export($balance, true)."\n";
    echo 'Seller balance value ?? 0: '.var_export($balance ?? 0, true)."\n";

    // Check order items sum
    $orderItems = OrderItem::where('seller_id', $seller->id);
    $totalRevenue = $orderItems->whereHas('order', function ($q) {
        $q->where('status', '!=', 'cancelled');
    })->sum('subtotal');
    echo 'totalRevenue sum(subtotal): '.var_export($totalRevenue, true)."\n";
    echo 'totalRevenue sum(subtotal) ?? 0: '.var_export($totalRevenue ?? 0, true)."\n";
}
