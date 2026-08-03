<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BannerController extends Controller
{
    public function index(): View
    {
        $banners = Banner::latest()->paginate(15);

        return view('admin.banners.index', compact('banners'));
    }

    public function create(): View
    {
        return view('admin.banners.form', ['banner' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'subtitle'     => 'nullable|string|max:500',
            'link'         => 'nullable|string|max:255',
            'image'        => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'mobile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'btn_text'     => 'nullable|string|max:100',
            'text_color'   => 'nullable|string|max:20',
            'bg_color'     => 'nullable|string|max:20',
            'sort_order'   => 'nullable|integer|min:0',
            'section'      => 'required|in:hero,promo,featured',
            'status'       => 'boolean',
        ]);

        $validated['image'] = $request->file('image')->store('banners', 'public');

        if ($request->hasFile('mobile_image')) {
            $validated['mobile_image'] = $request->file('mobile_image')->store('banners', 'public');
        }

        Banner::create($validated);

        return redirect()->route('admin.banners.index')
            ->with('success', 'Banner created successfully.');
    }

    public function edit(Banner $banner): View
    {
        return view('admin.banners.form', compact('banner'));
    }

    public function update(Request $request, Banner $banner): RedirectResponse
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'subtitle'     => 'nullable|string|max:500',
            'link'         => 'nullable|string|max:255',
            'btn_text'     => 'nullable|string|max:100',
            'text_color'   => 'nullable|string|max:20',
            'bg_color'     => 'nullable|string|max:20',
            'sort_order'   => 'nullable|integer|min:0',
            'section'      => 'required|in:hero,promo,featured',
            'status'       => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            $request->validate(['image' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048']);
            $validated['image'] = $request->file('image')->store('banners', 'public');
        }

        if ($request->hasFile('mobile_image')) {
            $request->validate(['mobile_image' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048']);
            $validated['mobile_image'] = $request->file('mobile_image')->store('banners', 'public');
        }

        $banner->update($validated);

        return redirect()->route('admin.banners.index')
            ->with('success', 'Banner updated successfully.');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        $banner->delete();

        return redirect()->route('admin.banners.index')
            ->with('success', 'Banner deleted successfully.');
    }
}
