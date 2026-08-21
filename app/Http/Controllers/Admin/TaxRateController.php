<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TaxRate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TaxRateController extends Controller
{
    public function index(): View
    {
        $taxRates = TaxRate::latest()->paginate(15);

        return view('admin.tax-rates.index', compact('taxRates'));
    }

    public function create(): View
    {
        return view('admin.tax-rates.form', [
            'taxRate' => null,
            'states' => static::states(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['state_code' => strtoupper(trim((string) $request->input('state_code')))]);

        $validated = $request->validate([
            'state_code' => ['required', 'string', 'size:2', Rule::unique('tax_rates', 'state_code')->whereNull('deleted_at')],
            'rate' => 'required|numeric|min:0|max:100',
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['state_code'] = strtoupper(trim($validated['state_code']));

        TaxRate::create($validated);

        return redirect()->route('admin.tax-rates.index')
            ->with('success', 'Tax rate created successfully.');
    }

    public function edit(TaxRate $taxRate): View
    {
        return view('admin.tax-rates.form', [
            'taxRate' => $taxRate,
            'states' => static::states(),
        ]);
    }

    private static function states(): array
    {
        return [
            'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas',
            'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut',
            'DE' => 'Delaware', 'FL' => 'Florida', 'GA' => 'Georgia',
            'HI' => 'Hawaii', 'ID' => 'Idaho', 'IL' => 'Illinois', 'IN' => 'Indiana',
            'IA' => 'Iowa', 'KS' => 'Kansas', 'KY' => 'Kentucky', 'LA' => 'Louisiana',
            'ME' => 'Maine', 'MD' => 'Maryland', 'MA' => 'Massachusetts',
            'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi',
            'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska',
            'NV' => 'Nevada', 'NH' => 'New Hampshire', 'NJ' => 'New Jersey',
            'NM' => 'New Mexico', 'NY' => 'New York', 'NC' => 'North Carolina',
            'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma',
            'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island',
            'SC' => 'South Carolina', 'SD' => 'South Dakota', 'TN' => 'Tennessee',
            'TX' => 'Texas', 'UT' => 'Utah', 'VT' => 'Vermont', 'VA' => 'Virginia',
            'WA' => 'Washington', 'WV' => 'West Virginia', 'WI' => 'Wisconsin',
            'WY' => 'Wyoming',
        ];
    }

    public function update(Request $request, TaxRate $taxRate): RedirectResponse
    {
        $request->merge(['state_code' => strtoupper(trim((string) $request->input('state_code')))]);

        $validated = $request->validate([
            'state_code' => ['required', 'string', 'size:2', Rule::unique('tax_rates', 'state_code')->ignore($taxRate->id)->whereNull('deleted_at')],
            'rate' => 'required|numeric|min:0|max:100',
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['state_code'] = strtoupper(trim($validated['state_code']));

        $taxRate->update($validated);

        return redirect()->route('admin.tax-rates.index')
            ->with('success', 'Tax rate updated successfully.');
    }

    public function toggle(TaxRate $taxRate): RedirectResponse
    {
        $taxRate->update(['is_active' => ! $taxRate->is_active]);

        return redirect()->route('admin.tax-rates.index')
            ->with('success', 'Tax rate '.($taxRate->is_active ? 'activated' : 'deactivated').' successfully.');
    }

    public function destroy(TaxRate $taxRate): RedirectResponse
    {
        $taxRate->delete();

        return redirect()->route('admin.tax-rates.index')
            ->with('success', 'Tax rate deleted successfully.');
    }
}
