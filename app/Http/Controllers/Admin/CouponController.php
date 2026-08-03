<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function index(): View
    {
        $coupons = Coupon::latest()->paginate(15);

        return view('admin.coupons.index', compact('coupons'));
    }

    public function create(): View
    {
        return view('admin.coupons.form', ['coupon' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code'              => 'required|string|max:50|unique:coupons,code',
            'type'              => 'required|in:percentage,fixed',
            'value'             => 'required|numeric|min:0',
            'min_order_amount'  => 'nullable|numeric|min:0',
            'max_discount'      => 'nullable|numeric|min:0',
            'usage_limit'       => 'nullable|integer|min:1',
            'per_user_limit'    => 'nullable|integer|min:1',
            'starts_at'         => 'nullable|date',
            'expires_at'        => 'nullable|date|after:starts_at',
            'is_active'         => 'boolean',
            'description'       => 'nullable|string|max:1000',
        ]);

        Coupon::create($validated);

        return redirect()->route('admin.coupons.index')
            ->with('success', 'Coupon created successfully.');
    }

    public function edit(Coupon $coupon): View
    {
        return view('admin.coupons.form', compact('coupon'));
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $validated = $request->validate([
            'code'              => 'required|string|max:50|unique:coupons,code,' . $coupon->id,
            'type'              => 'required|in:percentage,fixed',
            'value'             => 'required|numeric|min:0',
            'min_order_amount'  => 'nullable|numeric|min:0',
            'max_discount'      => 'nullable|numeric|min:0',
            'usage_limit'       => 'nullable|integer|min:1',
            'per_user_limit'    => 'nullable|integer|min:1',
            'starts_at'         => 'nullable|date',
            'expires_at'        => 'nullable|date|after:starts_at',
            'is_active'         => 'boolean',
            'description'       => 'nullable|string|max:1000',
        ]);

        $coupon->update($validated);

        return redirect()->route('admin.coupons.index')
            ->with('success', 'Coupon updated successfully.');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $coupon->delete();

        return redirect()->route('admin.coupons.index')
            ->with('success', 'Coupon deleted successfully.');
    }
}
