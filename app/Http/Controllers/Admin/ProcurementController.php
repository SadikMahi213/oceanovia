<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProcurementOrder;
use Illuminate\View\View;

class ProcurementController extends Controller
{
    public function index(): View
    {
        $procurements = ProcurementOrder::with(['order', 'supplier', 'seller'])
            ->latest()
            ->paginate(15);

        return view('admin.procurements.index', compact('procurements'));
    }

    public function show(ProcurementOrder $procurement): View
    {
        $procurement->load([
            'order.shippingAddress',
            'order.user',
            'supplier.supplierProfile',
            'seller.sellerProfile',
            'items.orderItem.product',
            'items.supplierProduct',
        ]);

        return view('admin.procurements.show', compact('procurement'));
    }
}