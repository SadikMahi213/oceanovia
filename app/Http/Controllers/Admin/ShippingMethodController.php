<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShippingMethodController extends Controller
{
    public function index(): View
    {
        $shippingMethods = ShippingMethod::ordered()->paginate(15);

        return view('admin.shipping-methods.index', compact('shippingMethods'));
    }

    public function create(): View
    {
        return view('admin.shipping-methods.form', ['shippingMethod' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:shipping_methods,code',
            'description' => 'nullable|string|max:1000',
            'base_rate' => 'required|numeric|min:0',
            'rate_per_kg' => 'required|numeric|min:0',
            'free_shipping_threshold' => 'nullable|numeric|min:0',
            'estimated_days_min' => 'nullable|integer|min:0',
            'estimated_days_max' => 'nullable|integer|min:0',
            'zones' => 'nullable|json',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        if (isset($validated['zones']) && is_string($validated['zones'])) {
            $validated['zones'] = json_decode($validated['zones'], true);
        }

        ShippingMethod::create($validated);

        return redirect()->route('admin.shipping-methods.index')
            ->with('success', 'Shipping method created successfully.');
    }

    public function edit(ShippingMethod $shippingMethod): View
    {
        return view('admin.shipping-methods.form', compact('shippingMethod'));
    }

    public function update(Request $request, ShippingMethod $shippingMethod): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:shipping_methods,code,'.$shippingMethod->id,
            'description' => 'nullable|string|max:1000',
            'base_rate' => 'required|numeric|min:0',
            'rate_per_kg' => 'required|numeric|min:0',
            'free_shipping_threshold' => 'nullable|numeric|min:0',
            'estimated_days_min' => 'nullable|integer|min:0',
            'estimated_days_max' => 'nullable|integer|min:0',
            'zones' => 'nullable|json',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        if (isset($validated['zones']) && is_string($validated['zones'])) {
            $validated['zones'] = json_decode($validated['zones'], true);
        }

        $shippingMethod->update($validated);

        return redirect()->route('admin.shipping-methods.index')
            ->with('success', 'Shipping method updated successfully.');
    }

    public function destroy(ShippingMethod $shippingMethod): RedirectResponse
    {
        $shippingMethod->delete();

        return redirect()->route('admin.shipping-methods.index')
            ->with('success', 'Shipping method deleted successfully.');
    }
}
