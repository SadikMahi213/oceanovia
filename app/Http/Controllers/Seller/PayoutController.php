<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\SellerPayout;
use App\Models\Setting;
use App\Services\PayoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PayoutController extends Controller
{
    public function index(PayoutService $payoutService): View
    {
        $sellerId = auth()->id();
        $balance = $payoutService->getBalance($sellerId);
        $payouts = SellerPayout::bySeller($sellerId)
            ->latest()
            ->paginate(15);

        return view('seller.payouts.index', compact('balance', 'payouts'));
    }

    public function create(PayoutService $payoutService): View
    {
        $sellerId = auth()->id();
        $balance = $payoutService->getBalance($sellerId);

        return view('seller.payouts.form', compact('balance'));
    }

    public function store(Request $request, PayoutService $payoutService): RedirectResponse
    {
        $sellerId = auth()->id();

        $validated = $request->validate([
            'amount'          => 'required|numeric|min:1',
            'payment_method'  => 'required|string|in:bank,paypal,stripe',
            'account_details' => 'required|array',
        ]);

        try {
            $payoutService->requestPayout(
                $sellerId,
                $validated['amount'],
                $validated['payment_method'],
                $validated['account_details']
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('seller.payouts.index')
            ->with('success', 'Payout request submitted successfully.');
    }

    public function withdrawalMethods(): View
    {
        $sellerId = auth()->id();
        $methods = Setting::get('seller.' . $sellerId . '.withdrawal_methods', '[]');
        $methods = is_array($methods) ? $methods : json_decode($methods, true) ?? [];

        return view('seller.payouts.methods', compact('methods'));
    }

    public function storeWithdrawalMethod(Request $request): RedirectResponse
    {
        $sellerId = auth()->id();

        $validated = $request->validate([
            'type'    => ['required', 'in:bank,paypal,mobile_banking,wise,payoneer,crypto'],
            'details' => ['required', 'array'],
        ]);

        $existing = Setting::get('seller.' . $sellerId . '.withdrawal_methods', '[]');
        $methods = is_array($existing) ? $existing : json_decode($existing, true) ?? [];

        $methods[] = [
            'id'      => (string) Str::uuid(),
            'type'    => $validated['type'],
            'details' => $validated['details'],
            'created_at' => now()->toDateTimeString(),
        ];

        Setting::set('seller.' . $sellerId . '.withdrawal_methods', json_encode($methods), 'seller');

        return redirect()->back()->with('success', 'Withdrawal method added successfully.');
    }

    public function destroyWithdrawalMethod($id): RedirectResponse
    {
        $sellerId = auth()->id();

        $existing = Setting::get('seller.' . $sellerId . '.withdrawal_methods', '[]');
        $methods = is_array($existing) ? $existing : json_decode($existing, true) ?? [];

        $methods = collect($methods)
            ->reject(fn($m) => ($m['id'] ?? null) === $id)
            ->values()
            ->toArray();

        Setting::set('seller.' . $sellerId . '.withdrawal_methods', json_encode($methods), 'seller');

        return redirect()->back()->with('success', 'Withdrawal method removed successfully.');
    }
}
