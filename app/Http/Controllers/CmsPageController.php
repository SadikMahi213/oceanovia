<?php

namespace App\Http\Controllers;

use App\Models\CmsPage;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CmsPageController extends Controller
{
    public function show(string $slug): View
    {
        $page = CmsPage::published()
            ->where('slug', $slug)
            ->firstOrFail();

        return view('pages.show', compact('page'));
    }
}
