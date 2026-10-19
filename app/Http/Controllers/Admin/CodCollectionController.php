<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CodCollection;
use App\Services\CodCollectionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CodCollectionController extends Controller
{
    public function index(Request $request): View
    {
        $collections = $this->filteredQuery($request)
            ->with(['order', 'orderItem', 'collector', 'collectedBy'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $summary = $this->filteredQuery($request)
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(expected_amount), 0) as expected, COALESCE(SUM(collected_amount), 0) as collected, COALESCE(SUM(shortfall_amount), 0) as shortfall, COALESCE(SUM(overpaid_amount), 0) as overpaid')
            ->first();

        return view('admin.cod-collections.index', compact('collections', 'summary'));
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->filteredQuery($request)
            ->with(['order', 'orderItem', 'collector'])
            ->latest();

        $filename = 'cod-collections-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $output = fopen('php://output', 'w');

            fputcsv($output, [
                'Order',
                'Product',
                'Collector Role',
                'Collector',
                'Expected',
                'Collected',
                'Shortfall',
                'Overpaid',
                'Status',
                'Requires Review',
                'Collected At',
            ]);

            $query->chunk(500, function ($collections) use ($output) {
                foreach ($collections as $collection) {
                    fputcsv($output, [
                        $collection->order?->order_number,
                        $collection->orderItem?->product_name,
                        $collection->collector_role,
                        $collection->collector?->name,
                        number_format((float) $collection->expected_amount, 2, '.', ''),
                        number_format((float) $collection->collected_amount, 2, '.', ''),
                        number_format((float) $collection->shortfall_amount, 2, '.', ''),
                        number_format((float) $collection->overpaid_amount, 2, '.', ''),
                        $collection->status,
                        $collection->requires_review ? 'yes' : 'no',
                        $collection->collected_at?->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function filteredQuery(Request $request): Builder
    {
        return CodCollection::query()
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->status))
            ->when($request->boolean('review'), fn (Builder $query) => $query->where('requires_review', true))
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = $request->search;

                $query->whereHas('order', fn (Builder $order) => $order->where('order_number', 'like', "%{$search}%"));
            });
    }

    public function show(CodCollection $collection): View
    {
        $collection->load(['order.items', 'orderItem.seller', 'orderItem.supplier', 'collector', 'collectedBy']);

        return view('admin.cod-collections.show', compact('collection'));
    }

    public function record(Request $request, CodCollection $collection, CodCollectionService $service): RedirectResponse
    {
        if ($collection->status !== 'pending') {
            return redirect()->back()->with('error', 'This collection has already been recorded.');
        }

        $validated = $request->validate([
            'collected_amount' => 'required|numeric|min:0',
            'notes'            => 'nullable|string|max:1000',
        ]);

        $service->recordCollection(
            $collection,
            (float) $validated['collected_amount'],
            (int) auth()->id(),
            $validated['notes'] ?? null,
        );

        return redirect()->route('admin.cod-collections.show', $collection)
            ->with('success', 'COD collection recorded.');
    }
}
