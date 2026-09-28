<x-app-layout>
    @section('title', 'KYC Verification')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-seller-sidebar :storeName="Auth::user()->sellerProfile?->store_name" :storeLogo="Auth::user()->sellerProfile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">KYC Verification</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Submit your identity documents for account approval</p>
                        </div>
                    </div>

                    @php
                        $kycStatus = $kyc?->status ?? 'not_submitted';
                        $kycBadge = match($kycStatus) {
                            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                            'approved' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                            'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                            default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                        };
                        $kycBadgeLabel = ucwords(str_replace('_', ' ', $kycStatus));
                    @endphp

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden mb-6">
                        <div class="p-5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <svg class="w-6 h-6 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">KYC Status</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                            @if($kycStatus === 'rejected' && $kyc?->admin_notes)
                                                Reason: {{ $kyc->admin_notes }}
                                            @elseif($kycStatus === 'approved' && $kyc?->verified_at)
                                                Verified on {{ $kyc->verified_at->format('M d, Y') }}
                                            @elseif($kycStatus === 'pending')
                                                Your documents are under review
                                            @else
                                                Upload your documents to start verification
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold {{ $kycBadge }}">{{ $kycBadgeLabel }}</span>
                            </div>
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

                    @if(session('error'))
                        <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-2xl p-4">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span class="text-sm font-medium text-red-800 dark:text-red-300">{{ session('error') }}</span>
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

                    @if($kycStatus === 'approved')
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-12 text-center">
                                <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Verification Complete</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Your identity documents have been verified. You can now add products and start selling.</p>
                            </div>
                        </div>
                    @else
                        <form method="POST" action="{{ route('seller.kyc.store') }}" enctype="multipart/form-data" class="space-y-6">
                            @csrf

                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                                <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Business Information</h2>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">These details verify your business. They are reviewed by our team and never shown publicly.</p>
                                </div>
                                <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <x-input-label for="company_name" value="Company Name (Legal Name)" />
                                        <x-text-input id="company_name" name="company_name" type="text" value="{{ old('company_name', $kyc?->company_name) }}" required maxlength="255"
                                            class="mt-1 block w-full" placeholder="e.g. ACME Traders LLC" />
                                        <x-input-error :messages="$errors->get('company_name')" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-input-label for="dba_name" value="DBA Name (Doing Business As)" />
                                        <x-text-input id="dba_name" name="dba_name" type="text" value="{{ old('dba_name', $kyc?->dba_name) }}" required maxlength="255"
                                            class="mt-1 block w-full" placeholder="e.g. ACME Store" />
                                        <x-input-error :messages="$errors->get('dba_name')" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-input-label for="tax_id" value="Tax ID (EIN / ITIN)" />
                                        <x-text-input id="tax_id" name="tax_id" type="text" value="{{ old('tax_id', $kyc?->tax_id) }}" required maxlength="100"
                                            class="mt-1 block w-full" placeholder="e.g. 12-3456789" />
                                        <x-input-error :messages="$errors->get('tax_id')" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-input-label for="resale_certificate" value="Resale Certificate" />
                                        <input type="file" id="resale_certificate" name="resale_certificate" accept=".jpg,.jpeg,.png,.pdf" required
                                            class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-market-50 dark:file:bg-market-900/30 file:text-market-700 dark:file:text-market-300 hover:file:bg-market-100 dark:hover:file:bg-market-900/50 cursor-pointer">
                                        <x-input-error :messages="$errors->get('resale_certificate')" class="mt-1" />
                                        @if($kyc?->resaleCertificateUrl)
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                Uploaded: <a href="{{ $kyc->resaleCertificateUrl }}" target="_blank" class="text-market-600 dark:text-market-400 hover:underline">{{ Str::limit(basename($kyc->resale_certificate), 30) }}</a>
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                                <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Upload Documents</h2>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Accepted formats: JPEG, PNG, WebP (max 5MB each), Resale Certificate accepts PDF</p>
                                </div>
                                <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <x-input-label for="document_type" value="Document Type" />
                                        <select id="document_type" name="document_type" required
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 text-sm focus:border-market-500 focus:ring-market-500 dark:focus:border-market-400 dark:focus:ring-market-400">
                                            <option value="">Select document type</option>
                                            @foreach(['passport' => 'Passport', 'drivers_license' => "Driver's License", 'national_id' => 'National ID', 'business_license' => 'Business License'] as $value => $label)
                                                <option value="{{ $value }}" @selected(old('document_type', $kyc?->document_type) === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <x-input-error :messages="$errors->get('document_type')" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-input-label for="document_number" value="Document Number" />
                                        <x-text-input id="document_number" name="document_number" type="text" value="{{ old('document_number', $kyc?->document_number) }}" required maxlength="100"
                                            class="mt-1 block w-full" placeholder="e.g. A1234567" />
                                        <x-input-error :messages="$errors->get('document_number')" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-input-label for="document_front" value="Document (Front)" />
                                        <input type="file" id="document_front" name="document_front" accept="image/*" required
                                            class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-market-50 dark:file:bg-market-900/30 file:text-market-700 dark:file:text-market-300 hover:file:bg-market-100 dark:hover:file:bg-market-900/50 cursor-pointer">
                                        <x-input-error :messages="$errors->get('document_front')" class="mt-1" />
                                        @if($kyc?->documentFrontUrl)
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                Uploaded: <a href="{{ $kyc->documentFrontUrl }}" target="_blank" class="text-market-600 dark:text-market-400 hover:underline">{{ Str::limit(basename($kyc->document_front), 30) }}</a>
                                            </p>
                                        @endif
                                    </div>
                                    <div>
                                        <x-input-label for="document_back" value="Document (Back)" />
                                        <input type="file" id="document_back" name="document_back" accept="image/*"
                                            class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-market-50 dark:file:bg-market-900/30 file:text-market-700 dark:file:text-market-300 hover:file:bg-market-100 dark:hover:file:bg-market-900/50 cursor-pointer">
                                        <x-input-error :messages="$errors->get('document_back')" class="mt-1" />
                                        @if($kyc?->documentBackUrl)
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                Uploaded: <a href="{{ $kyc->documentBackUrl }}" target="_blank" class="text-market-600 dark:text-market-400 hover:underline">{{ Str::limit(basename($kyc->document_back), 30) }}</a>
                                            </p>
                                        @endif
                                    </div>
                                    <div>
                                        <x-input-label for="selfie" value="Selfie" />
                                        <input type="file" id="selfie" name="selfie" accept="image/*"
                                            class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-market-50 dark:file:bg-market-900/30 file:text-market-700 dark:file:text-market-300 hover:file:bg-market-100 dark:hover:file:bg-market-900/50 cursor-pointer">
                                        <x-input-error :messages="$errors->get('selfie')" class="mt-1" />
                                        @if($kyc?->selfie)
                                            <img src="{{ asset('storage/' . $kyc->selfie) }}" alt="Selfie" class="mt-2 max-w-xs rounded-xl border border-gray-200 dark:border-gray-600">
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-end">
                                <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    Submit for Verification
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-app-layout>