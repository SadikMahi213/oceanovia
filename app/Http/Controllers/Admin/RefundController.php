<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Refund;
use App\Services\RefundService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RefundController extends Controller
{
    public function index(): View
    {
        $refunds = Refund::with('order', 'user')
            ->when(in_array(request('status'), ['pending', 'approved', 'stripe_refunded', 'completed', 'rejected'], true), fn ($query) => $query->where('status', request('status')))
            ->orderByRaw("CASE status WHEN 'pending' THEN 1 WHEN 'approved' THEN 2 WHEN 'stripe_refunded' THEN 3 WHEN 'completed' THEN 4 WHEN 'rejected' THEN 5 ELSE 6 END")
            ->latest()
            ->paginate(15);

        return view('admin.refunds.index', compact('refunds'));
    }

    public function show(Refund $refund): View
    {
        $refund->load('order', 'user', 'approver', 'completer', 'orderItem');

        return view('admin.refunds.show', compact('refund'));
    }

    public function approve(Request $request, Refund $refund, RefundService $refundService): RedirectResponse
    {
        $isCard = $refund->order?->payment_method === 'stripe';

        if ($refund->status !== 'pending') {
            // A card refund can be approved again. An attempt that did not
            // reach the payment provider is sent again with the same
            // idempotency key, and a refund the provider already confirmed
            // only needs its local finalization. Both are safe to repeat.
            $resumable = $isCard && in_array($refund->status, ['approved', RefundService::STATUS_STRIPE_REFUNDED], true);

            if (! $resumable) {
                return redirect()->back()->with('error', 'Only pending refunds can be approved.');
            }
        }

        $isCod = ! $isCard;

        $validated = $request->validate([
            'notes'         => 'nullable|string|max:1000',
            'refund_method' => $isCod ? 'required|in:cash,bank_transfer,store_credit,other' : 'nullable|string|max:30',
        ]);

        try {
            $refundService->approve(
                $refund,
                auth()->id(),
                $validated['notes'] ?? '',
                $validated['refund_method'] ?? null,
            );
        } catch (\Throwable $e) {
            // The refund state says whether the payment provider already
            // refunded, so the operator can finish or repeat the operation.
            return redirect()->back()->with(
                'error',
                'The refund could not be finished. Its state shows whether the payment provider already refunded it.'
            );
        }

        return redirect()->route('admin.refunds.show', $refund)
            ->with('success', $isCod
                ? 'Refund approved. Complete it once cash has been returned.'
                : 'Refund approved successfully.');
    }

    public function complete(Request $request, Refund $refund, RefundService $refundService): RedirectResponse
    {
        if ($refund->order?->payment_method === 'stripe') {
            // A card refund is made by the payment provider. It can only be
            // completed once the provider confirmed it, which is the state
            // the service knows as stripe_refunded.
            if ($refund->status !== RefundService::STATUS_STRIPE_REFUNDED) {
                return redirect()->back()->with('error', 'Stripe refunds complete automatically once the provider confirmed them.');
            }
        } elseif ($refund->status !== 'approved') {
            return redirect()->back()->with('error', 'Only approved refunds can be completed.');
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $refundService->complete($refund, auth()->id(), $validated['notes'] ?? '');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'The refund could not be completed.');
        }

        return redirect()->route('admin.refunds.show', $refund)
            ->with('success', 'Refund marked as completed.');
    }

    public function reject(Request $request, Refund $refund, RefundService $refundService): RedirectResponse
    {
        if ($refund->status !== 'pending') {
            return redirect()->back()->with('error', 'Only pending refunds can be rejected.');
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        $refundService->reject($refund, auth()->id(), $validated['reason'] ?? '');

        return redirect()->route('admin.refunds.index')
            ->with('success', 'Refund rejected successfully.');
    }
}
