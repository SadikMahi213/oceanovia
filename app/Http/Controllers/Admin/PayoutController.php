<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\PayoutStateException;
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

        try {
            $payoutService->approve($payout);
        } catch (PayoutStateException $e) {
            // The service is the boundary that keeps the payout on its path.
            // The check above is for the message, this one for the case that
            // another request moved the payout in the meantime.
            return redirect()->back()->with('error', 'Only pending payouts can be approved.');
        }

        return redirect()->route('admin.payouts.index')
            ->with('success', 'Payout approved successfully.');
    }

    public function complete(SellerPayout $payout, PayoutService $payoutService): RedirectResponse
    {
        if ($payout->status !== 'approved') {
            return redirect()->back()->with('error', 'Only approved payouts can be completed.');
        }

        try {
            $payoutService->complete($payout);
        } catch (PayoutStateException $e) {
            return redirect()->back()->with('error', 'Only approved payouts can be completed.');
        }

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

        try {
            $payoutService->reject($payout, $validated['reason'] ?? '');
        } catch (PayoutStateException $e) {
            return redirect()->back()->with('error', 'Only pending payouts can be rejected.');
        }

        return redirect()->route('admin.payouts.index')
            ->with('success', 'Payout rejected successfully.');
    }
}
