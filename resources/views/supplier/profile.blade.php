<x-app-layout>
    @section('title', 'Supplier Profile')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-supplier-sidebar :companyName="$profile?->company_name" :companyLogo="$profile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Supplier Profile</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage your company information and settings</p>
                        </div>
                    </div>

                    @if(session('success'))
                        <div class="mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-2xl p-4">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span class="text-sm font-medium text-green-800 dark:text-green-300">{{ session('success') }}</span>
                            </div>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-2xl p-4">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span class="text-sm font-medium text-red-800 dark:text-red-300">Please fix the following errors:</span>
                            </div>
                            <ul class="list-disc list-inside text-sm text-red-600 dark:text-red-400 space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('supplier.profile.update') }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Logo & Banner</h2>
                            </div>
                            <div class="p-5 space-y-4">
                                <div>
                                    <x-input-label for="company_logo" value="Company Logo" />
                                    <input type="file" id="company_logo" name="company_logo" accept="image/*"
                                        class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-market-50 dark:file:bg-market-900/30 file:text-market-700 dark:file:text-market-300 hover:file:bg-market-100 dark:hover:file:bg-market-900/50 cursor-pointer">
                                    <x-input-error :messages="$errors->get('company_logo')" class="mt-1" />
                                    @if($profile?->logo_url)
                                        <div class="mt-3">
                                            <img src="{{ $profile->logo_url }}" alt="Current logo" class="w-20 h-20 rounded-xl object-cover border border-gray-200 dark:border-gray-600">
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <x-input-label for="company_banner" value="Company Banner" />
                                    <input type="file" id="company_banner" name="company_banner" accept="image/*"
                                        class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-market-50 dark:file:bg-market-900/30 file:text-market-700 dark:file:text-market-300 hover:file:bg-market-100 dark:hover:file:bg-market-900/50 cursor-pointer">
                                    <x-input-error :messages="$errors->get('company_banner')" class="mt-1" />
                                    @if($profile?->banner_url)
                                        <div class="mt-3">
                                            <img src="{{ $profile->banner_url }}" alt="Current banner" class="w-full max-w-md h-24 rounded-xl object-cover border border-gray-200 dark:border-gray-600">
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Company Information</h2>
                            </div>
                            <div class="p-5 space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label for="company_name" value="Company Name *" />
                                        <input type="text" id="company_name" name="company_name" value="{{ old('company_name', $profile?->company_name) }}" required
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <x-input-error :messages="$errors->get('company_name')" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-input-label for="brand_name" value="Brand Name" />
                                        <input type="text" id="brand_name" name="brand_name" value="{{ old('brand_name', $profile?->brand_name) }}"
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <x-input-error :messages="$errors->get('brand_name')" class="mt-1" />
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label for="company_slug" value="Company Slug" />
                                        <input type="text" id="company_slug" name="company_slug" value="{{ old('company_slug', $profile?->company_slug) }}"
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">URL-friendly name (auto-generated if empty)</p>
                                        <x-input-error :messages="$errors->get('company_slug')" class="mt-1" />
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <x-input-label for="trade_license" value="Trade License" />
                                            <input type="text" id="trade_license" name="trade_license" value="{{ old('trade_license', $profile?->trade_license) }}"
                                                class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                            <x-input-error :messages="$errors->get('trade_license')" class="mt-1" />
                                        </div>
                                        <div>
                                            <x-input-label for="vat_number" value="VAT Number" />
                                            <input type="text" id="vat_number" name="vat_number" value="{{ old('vat_number', $profile?->vat_number) }}"
                                                class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                            <x-input-error :messages="$errors->get('vat_number')" class="mt-1" />
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <x-input-label for="description" value="Company Description" />
                                    <textarea id="description" name="description" rows="4"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">{{ old('description', $profile?->description) }}</textarea>
                                    <x-input-error :messages="$errors->get('description')" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Addresses</h2>
                            </div>
                            <div class="p-5 space-y-4">
                                <div>
                                    <x-input-label for="address" value="Business Address" />
                                    <textarea id="address" name="address" rows="3"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">{{ old('address', $profile?->address) }}</textarea>
                                    <x-input-error :messages="$errors->get('address')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="warehouse_address" value="Warehouse Address" />
                                    <textarea id="warehouse_address" name="warehouse_address" rows="3"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">{{ old('warehouse_address', $profile?->warehouse_address) }}</textarea>
                                    <x-input-error :messages="$errors->get('warehouse_address')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="pickup_address" value="Pickup Address" />
                                    <textarea id="pickup_address" name="pickup_address" rows="3"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">{{ old('pickup_address', $profile?->pickup_address) }}</textarea>
                                    <x-input-error :messages="$errors->get('pickup_address')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="return_address" value="Return Address" />
                                    <textarea id="return_address" name="return_address" rows="3"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">{{ old('return_address', $profile?->return_address) }}</textarea>
                                    <x-input-error :messages="$errors->get('return_address')" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Contact</h2>
                            </div>
                            <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="phone" value="Phone Number" />
                                    <input type="text" id="phone" name="phone" value="{{ old('phone', $profile?->phone) }}"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="contact_email" value="Contact Email" />
                                    <input type="email" id="contact_email" name="contact_email" value="{{ old('contact_email', $profile?->contact_email) }}"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('contact_email')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="contact_person" value="Contact Person" />
                                    <input type="text" id="contact_person" name="contact_person" value="{{ old('contact_person', $profile?->contact_person) }}"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('contact_person')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="website" value="Website" />
                                    <input type="url" id="website" name="website" value="{{ old('website', $profile?->website) }}"
                                        placeholder="https://example.com"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                    <x-input-error :messages="$errors->get('website')" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Bank Account</h2>
                            </div>
                            <div class="p-5">
                                @php
                                    $rawBank = old('bank_account', $profile?->bank_account ?? []);
                                    $bankEntries = [];
                                    if (!empty($rawBank)) {
                                        $isIndexed = array_keys($rawBank) === range(0, count($rawBank) - 1) && isset($rawBank[0]['key'] ?? null);
                                        if ($isIndexed) {
                                            $bankEntries = $rawBank;
                                        } else {
                                            foreach ($rawBank as $k => $v) {
                                                $bankEntries[] = ['key' => $k, 'value' => $v];
                                            }
                                        }
                                    }
                                @endphp
                                <div id="bank-account-container" class="space-y-3">
                                    @forelse($bankEntries as $entry)
                                        <div class="bank-row flex items-start gap-3">
                                            <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-3">
                                                <div>
                                                    <input type="text" name="bank_account[{{ $loop->index }}][key]" value="{{ $entry['key'] }}"
                                                        placeholder="Field name (e.g. bank_name)"
                                                        class="block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                                </div>
                                                <div>
                                                    <input type="text" name="bank_account[{{ $loop->index }}][value]" value="{{ $entry['value'] }}"
                                                        placeholder="Field value"
                                                        class="block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                                </div>
                                            </div>
                                            <button type="button" onclick="this.closest('.bank-row').remove()"
                                                class="mt-1 p-2 text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors shrink-0">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    @empty
                                        <div class="bank-row flex items-start gap-3">
                                            <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-3">
                                                <div>
                                                    <input type="text" name="bank_account[0][key]" value=""
                                                        placeholder="Field name (e.g. bank_name)"
                                                        class="block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                                </div>
                                                <div>
                                                    <input type="text" name="bank_account[0][value]" value=""
                                                        placeholder="Field value"
                                                        class="block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
                                                </div>
                                            </div>
                                            <button type="button" onclick="this.closest('.bank-row').remove()"
                                                class="mt-1 p-2 text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors shrink-0">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    @endforelse
                                </div>
                                <button type="button" onclick="addBankRow()"
                                    class="mt-3 inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-market-600 dark:text-market-400 bg-market-50 dark:bg-market-900/20 hover:bg-market-100 dark:hover:bg-market-900/30 rounded-xl transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Add Field
                                </button>
                                <x-input-error :messages="$errors->get('bank_account')" class="mt-1" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>

<script>
function addBankRow() {
    const container = document.getElementById('bank-account-container');
    const index = container.children.length;
    const div = document.createElement('div');
    div.className = 'bank-row flex items-start gap-3';
    div.innerHTML = `
        <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
                <input type="text" name="bank_account[${index}][key]" value=""
                    placeholder="Field name (e.g. bank_name)"
                    class="block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
            </div>
            <div>
                <input type="text" name="bank_account[${index}][value]" value=""
                    placeholder="Field value"
                    class="block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">
            </div>
        </div>
        <button type="button" onclick="this.closest('.bank-row').remove()"
            class="mt-1 p-2 text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </button>
    `;
    container.appendChild(div);
}
</script>
