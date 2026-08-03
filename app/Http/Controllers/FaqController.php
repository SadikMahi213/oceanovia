<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\View\View;

class FaqController extends Controller
{
    public function index(): View
    {
        $categories = ['general', 'shipping', 'returns', 'payments', 'account', 'other'];

        $faqs = Faq::active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('category');

        return view('faq.index', compact('faqs', 'categories'));
    }
}
