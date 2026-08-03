<x-app-layout>
    @section('title', 'KYC Verification')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-supplier-sidebar :companyName="Auth::user()->supplierProfile?->company_name" :companyLogo="Auth::user()->supplierProfile?->logo_url" />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">KYC Verification</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Submit your identity and business verification documents</p>
                        </div>
                    </div>

                    @php
                        $kycBadge = match($profile?->kyc_status) {
                            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                            'verified' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                            'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                            default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                        };
                    @endphp

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden mb-6">
                        <div class="p-5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <svg class="w-6 h-6 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">KYC Status</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                            @if($profile?->kyc_status === 'rejected' && $profile?->kyc_rejection_reason)
                                                Reason: {{ $profile->kyc_rejection_reason }}
                                            @elseif($profile?->kyc_status === 'verified' && $profile?->kyc_verified_at)
                                                Verified on {{ $profile->kyc_verified_at->format('M d, Y') }}
                                            @else
                                                Verification helps build trust with customers
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold {{ $kycBadge }}">{{ $profile?->kyc_status_label ?? 'Not Submitted' }}</span>
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

                    @if($profile?->kyc_status === 'verified')
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="p-12 text-center">
                                <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Verification Complete</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Your identity and business documents have been verified. You have full access to all supplier features.</p>
                            </div>
                        </div>
                    @else
                        <form method="POST" action="{{ route('supplier.kyc.submit') }}" enctype="multipart/form-data" class="space-y-6">
                            @csrf
                            @method('POST')

                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                                <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Upload Documents</h2>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Accepted formats: JPEG, PNG, PDF (max 5MB each)</p>
                                </div>
                                <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <x-input-label for="national_id" value="National ID" />
                                        <input type="file" id="national_id" name="national_id" accept="image/*,.pdf"
                                            class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-market-50 dark:file:bg-market-900/30 file:text-market-700 dark:file:text-market-300 hover:file:bg-market-100 dark:hover:file:bg-market-900/50 cursor-pointer">
                                        <x-input-error :messages="$errors->get('national_id')" class="mt-1" />
                                        @if($profile?->national_id)
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                Uploaded: <a href="{{ $profile->national_id_url }}" target="_blank" class="text-market-600 dark:text-market-400 hover:underline">{{ Str::limit(basename($profile->national_id), 30) }}</a>
                                            </p>
                                        @endif
                                    </div>
                                    <div>
                                        <x-input-label for="passport" value="Passport" />
                                        <input type="file" id="passport" name="passport" accept="image/*,.pdf"
                                            class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-market-50 dark:file:bg-market-900/30 file:text-market-700 dark:file:text-market-300 hover:file:bg-market-100 dark:hover:file:bg-market-900/50 cursor-pointer">
                                        <x-input-error :messages="$errors->get('passport')" class="mt-1" />
                                        @if($profile?->passport)
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                Uploaded: <a href="{{ $profile->passport_url }}" target="_blank" class="text-market-600 dark:text-market-400 hover:underline">{{ Str::limit(basename($profile->passport), 30) }}</a>
                                            </p>
                                        @endif
                                    </div>
                                    <div>
                                        <x-input-label for="business_license_file" value="Business License" />
                                        <input type="file" id="business_license_file" name="business_license_file" accept="image/*,.pdf"
                                            class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-market-50 dark:file:bg-market-900/30 file:text-market-700 dark:file:text-market-300 hover:file:bg-market-100 dark:hover:file:bg-market-900/50 cursor-pointer">
                                        <x-input-error :messages="$errors->get('business_license_file')" class="mt-1" />
                                        @if($profile?->business_license_file)
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                Uploaded: <a href="{{ $profile->business_license_url }}" target="_blank" class="text-market-600 dark:text-market-400 hover:underline">{{ Str::limit(basename($profile->business_license_file), 30) }}</a>
                                            </p>
                                        @endif
                                    </div>
                                    <div>
                                        <x-input-label for="tax_certificate" value="Tax Certificate" />
                                        <input type="file" id="tax_certificate" name="tax_certificate" accept="image/*,.pdf"
                                            class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-market-50 dark:file:bg-market-900/30 file:text-market-700 dark:file:text-market-300 hover:file:bg-market-100 dark:hover:file:bg-market-900/50 cursor-pointer">
                                        <x-input-error :messages="$errors->get('tax_certificate')" class="mt-1" />
                                        @if($profile?->tax_certificate)
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                Uploaded: <a href="{{ $profile->tax_certificate_url }}" target="_blank" class="text-market-600 dark:text-market-400 hover:underline">{{ Str::limit(basename($profile->tax_certificate), 30) }}</a>
                                            </p>
                                        @endif
                                    </div>
                                    <div>
                                        <x-input-label for="company_registration_doc" value="Company Registration" />
                                        <input type="file" id="company_registration_doc" name="company_registration_doc" accept="image/*,.pdf"
                                            class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-market-50 dark:file:bg-market-900/30 file:text-market-700 dark:file:text-market-300 hover:file:bg-market-100 dark:hover:file:bg-market-900/50 cursor-pointer">
                                        <x-input-error :messages="$errors->get('company_registration_doc')" class="mt-1" />
                                        @if($profile?->company_registration_doc)
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                Uploaded: <a href="{{ $profile->company_registration_url }}" target="_blank" class="text-market-600 dark:text-market-400 hover:underline">{{ Str::limit(basename($profile->company_registration_doc), 30) }}</a>
                                            </p>
                                        @endif
                                    </div>
                                    <div>
                                        <x-input-label for="bank_verification_doc" value="Bank Verification" />
                                        <input type="file" id="bank_verification_doc" name="bank_verification_doc" accept="image/*,.pdf"
                                            class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-market-50 dark:file:bg-market-900/30 file:text-market-700 dark:file:text-market-300 hover:file:bg-market-100 dark:hover:file:bg-market-900/50 cursor-pointer">
                                        <x-input-error :messages="$errors->get('bank_verification_doc')" class="mt-1" />
                                        @if($profile?->bank_verification_doc)
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                Uploaded: <a href="{{ $profile->bank_verification_url }}" target="_blank" class="text-market-600 dark:text-market-400 hover:underline">{{ Str::limit(basename($profile->bank_verification_doc), 30) }}</a>
                                            </p>
                                        @endif
                                    </div>
                                    <div>
                                        <x-input-label for="address_verification_doc" value="Address Verification" />
                                        <input type="file" id="address_verification_doc" name="address_verification_doc" accept="image/*,.pdf"
                                            class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-market-50 dark:file:bg-market-900/30 file:text-market-700 dark:file:text-market-300 hover:file:bg-market-100 dark:hover:file:bg-market-900/50 cursor-pointer">
                                        <x-input-error :messages="$errors->get('address_verification_doc')" class="mt-1" />
                                        @if($profile?->address_verification_doc)
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                Uploaded: <a href="{{ $profile->address_verification_url }}" target="_blank" class="text-market-600 dark:text-market-400 hover:underline">{{ Str::limit(basename($profile->address_verification_doc), 30) }}</a>
                                            </p>
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
