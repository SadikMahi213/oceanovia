<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SellerPayout;
use App\Services\PayoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayoutController extends Controller
{
    public function index(): View
    {
        $payouts = SellerPayout::with('seller')
            ->orderBy('created_at')
            ->paginate(15);

        return view('admin.payouts.index', compact('payouts'));
    }

    public function show(SellerPayout $payout): View
    {
        $payout->load('seller', 'approver', 'commissions');

        return view('admin.payouts.show', compact('payout'));
    }

    public function approve(SellerPayout $payout, PayoutService $payoutService): RedirectResponse
    {
        if ($payout->status !== 'pending') {
            return redirect()->back()->with('error', 'Only pending payouts can be approved.');
        }

        $payoutService->approve($payout);

        return redirect()->route('admin.payouts.index')
            ->with('success', 'Payout approved successfully.');
    }

    public function complete(SellerPayout $payout, PayoutService $payoutService): RedirectResponse
    {
        if ($payout->status !== 'approved') {
            return redirect()->back()->with('error', 'Only approved payouts can be completed.');
        }

        $payoutService->complete($payout);

        return redirect()->route('admin.payouts.index')
            ->with('success', 'Payout completed successfully.');
    }

    public function reject(Request $request, SellerPayout $payout, PayoutService $payoutService): RedirectResponse
    {
        if ($payout->status !== 'pending') {
            return redirect()->back()->with('error', 'Only pending payouts can be rejected.');
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        $payoutService->reject($payout, $validated['reason'] ?? '');

        return redirect()->route('admin.payouts.index')
            ->with('success', 'Payout rejected successfully.');
    }
}
