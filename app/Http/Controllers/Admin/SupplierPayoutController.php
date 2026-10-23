<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\PayoutStateException;
use App\Http\Controllers\Controller;
use App\Models\SupplierPayout;
use App\Services\SupplierPayoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SupplierPayoutController extends Controller
{
    public function __construct(private SupplierPayoutService $payouts) {}

    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();

        $payouts = SupplierPayout::with('supplier')
            ->when(in_array($status, ['pending', 'processing', 'completed', 'rejected'], true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $summary = [
            'pending' => SupplierPayout::where('status', SupplierPayout::STATUS_PENDING)->count(),
            'processing' => SupplierPayout::where('status', SupplierPayout::STATUS_PROCESSING)->count(),
            'completed' => SupplierPayout::where('status', SupplierPayout::STATUS_COMPLETED)->count(),
            'rejected' => SupplierPayout::where('status', SupplierPayout::STATUS_REJECTED)->count(),
        ];

        return view('admin.supplier-payouts.index', compact('payouts', 'status', 'summary'));
    }

    public function show(SupplierPayout $supplierPayout): View
    {
        $supplierPayout->load(['supplier', 'processor']);

        return view('admin.supplier-payouts.show', ['payout' => $supplierPayout]);
    }

    public function approve(Request $request, SupplierPayout $supplierPayout): RedirectResponse
    {
        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->payouts->approve($supplierPayout, $request->user()->id, $validated['admin_notes'] ?? null);
        } catch (PayoutStateException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Supplier payout approval failed', ['payout_id' => $supplierPayout->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'Unable to approve this supplier payout.');
        }

        return back()->with('success', 'Supplier payout approved for processing.');
    }

    public function complete(Request $request, SupplierPayout $supplierPayout): RedirectResponse
    {
        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->payouts->complete($supplierPayout, $request->user()->id, $validated['admin_notes'] ?? null);
        } catch (PayoutStateException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Supplier payout completion failed', ['payout_id' => $supplierPayout->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'Unable to complete this supplier payout.');
        }

        return back()->with('success', 'Supplier payout completed.');
    }

    public function reject(Request $request, SupplierPayout $supplierPayout): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:2000'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->payouts->reject(
                $supplierPayout,
                $request->user()->id,
                $validated['rejection_reason'],
                $validated['admin_notes'] ?? null,
            );
        } catch (PayoutStateException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Supplier payout rejection failed', ['payout_id' => $supplierPayout->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'Unable to reject this supplier payout.');
        }

        return back()->with('success', 'Supplier payout rejected and funds returned.');
    }
}
