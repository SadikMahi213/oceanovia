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
            ->when(in_array(request('status'), ['pending', 'approved', 'completed', 'rejected'], true), fn ($query) => $query->where('status', request('status')))
            ->orderByRaw("CASE status WHEN 'pending' THEN 1 WHEN 'approved' THEN 2 WHEN 'completed' THEN 3 WHEN 'rejected' THEN 4 ELSE 5 END")
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
        if ($refund->status !== 'pending') {
            return redirect()->back()->with('error', 'Only pending refunds can be approved.');
        }

        $isCod = $refund->order?->payment_method !== 'stripe';

        $validated = $request->validate([
            'notes'         => 'nullable|string|max:1000',
            'refund_method' => $isCod ? 'required|in:cash,bank_transfer,store_credit,other' : 'nullable|string|max:30',
        ]);

        $refundService->approve(
            $refund,
            auth()->id(),
            $validated['notes'] ?? '',
            $validated['refund_method'] ?? null,
        );

        return redirect()->route('admin.refunds.show', $refund)
            ->with('success', $isCod
                ? 'Refund approved. Complete it once cash has been returned.'
                : 'Refund approved successfully.');
    }

    public function complete(Request $request, Refund $refund, RefundService $refundService): RedirectResponse
    {
        if ($refund->status !== 'approved') {
            return redirect()->back()->with('error', 'Only approved refunds can be completed.');
        }

        if ($refund->order?->payment_method === 'stripe') {
            return redirect()->back()->with('error', 'Stripe refunds complete automatically.');
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $refundService->complete($refund, auth()->id(), $validated['notes'] ?? '');

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
