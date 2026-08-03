<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FaqController extends Controller
{
    public function index(Request $request): View
    {
        $query = Faq::query();

        if ($category = $request->get('category')) {
            $query->where('category', $category);
        }

        $faqs = $query->latest()->paginate(15);

        $categories = ['general', 'shipping', 'returns', 'payments', 'account', 'other'];

        return view('admin.faqs.index', compact('faqs', 'categories'));
    }

    public function create(): View
    {
        $categories = ['general', 'shipping', 'returns', 'payments', 'account', 'other'];

        return view('admin.faqs.form', ['faq' => null, 'categories' => $categories]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'question'   => 'required|string|max:1000',
            'answer'     => 'required|string',
            'category'   => 'required|string|in:general,shipping,returns,payments,account,other',
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'boolean',
        ]);

        Faq::create($validated);

        return redirect()->route('admin.faqs.index')
            ->with('success', 'FAQ created successfully.');
    }

    public function edit(Faq $faq): View
    {
        $categories = ['general', 'shipping', 'returns', 'payments', 'account', 'other'];

        return view('admin.faqs.form', compact('faq', 'categories'));
    }

    public function update(Request $request, Faq $faq): RedirectResponse
    {
        $validated = $request->validate([
            'question'   => 'required|string|max:1000',
            'answer'     => 'required|string',
            'category'   => 'required|string|in:general,shipping,returns,payments,account,other',
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'boolean',
        ]);

        $faq->update($validated);

        return redirect()->route('admin.faqs.index')
            ->with('success', 'FAQ updated successfully.');
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $faq->delete();

        return redirect()->route('admin.faqs.index')
            ->with('success', 'FAQ deleted successfully.');
    }
}
