<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommissionController extends Controller
{
    public function index(): View
    {
        $query = Commission::with(['order', 'seller'])->latest();

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        if ($sellerId = request('seller_id')) {
            $query->where('seller_id', $sellerId);
        }

        if ($search = request('search')) {
            $query->whereHas('order', function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%");
            });
        }

        $commissions = $query->paginate(15);

        return view('admin.commissions.index', compact('commissions'));
    }

    public function show(Commission $commission): View
    {
        $commission->load(['order', 'orderItem', 'seller', 'payout']);

        return view('admin.commissions.show', compact('commission'));
    }

    public function markPaid(Commission $commission): RedirectResponse
    {
        if ($commission->status === 'paid') {
            return redirect()->route('admin.commissions.index')
                ->with('info', 'Commission is already marked as paid.');
        }

        $commission->update([
            'status'  => 'paid',
            'paid_at' => now(),
        ]);

        return redirect()->route('admin.commissions.index')
            ->with('success', 'Commission marked as paid successfully.');
    }
}
