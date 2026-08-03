<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CmsPageController extends Controller
{
    public function index(): View
    {
        $cmsPages = CmsPage::latest()->paginate(15);

        return view('admin.cms-pages.index', compact('cmsPages'));
    }

    public function create(): View
    {
        return view('admin.cms-pages.form', ['cmsPage' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'slug'             => 'nullable|string|max:255|unique:cms_pages,slug',
            'content'          => 'nullable|string',
            'meta_title'       => 'nullable|string|max:500',
            'meta_description' => 'nullable|string|max:1000',
            'status'           => 'required|in:draft,published',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        CmsPage::create($validated);

        return redirect()->route('admin.cms-pages.index')
            ->with('success', 'CMS page created successfully.');
    }

    public function edit(CmsPage $cmsPage): View
    {
        return view('admin.cms-pages.form', compact('cmsPage'));
    }

    public function update(Request $request, CmsPage $cmsPage): RedirectResponse
    {
        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'slug'             => 'nullable|string|max:255|unique:cms_pages,slug,' . $cmsPage->id,
            'content'          => 'nullable|string',
            'meta_title'       => 'nullable|string|max:500',
            'meta_description' => 'nullable|string|max:1000',
            'status'           => 'required|in:draft,published',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $cmsPage->update($validated);

        return redirect()->route('admin.cms-pages.index')
            ->with('success', 'CMS page updated successfully.');
    }

    public function destroy(CmsPage $cmsPage): RedirectResponse
    {
        $cmsPage->delete();

        return redirect()->route('admin.cms-pages.index')
            ->with('success', 'CMS page deleted successfully.');
    }
}
