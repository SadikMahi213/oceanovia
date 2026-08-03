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
            ->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected')")
            ->latest()
            ->paginate(15);

        return view('admin.refunds.index', compact('refunds'));
    }

    public function show(Refund $refund): View
    {
        $refund->load('order', 'user', 'approver', 'orderItem');

        return view('admin.refunds.show', compact('refund'));
    }

    public function approve(Request $request, Refund $refund, RefundService $refundService): RedirectResponse
    {
        if ($refund->status !== 'pending') {
            return redirect()->back()->with('error', 'Only pending refunds can be approved.');
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $refundService->approve($refund, auth()->id(), $validated['notes'] ?? '');

        return redirect()->route('admin.refunds.index')
            ->with('success', 'Refund approved successfully.');
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
