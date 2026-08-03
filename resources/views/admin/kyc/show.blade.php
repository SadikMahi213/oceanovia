<x-app-layout>
    @section('title', 'KYC Verification Detail')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-admin-sidebar />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">KYC Verification</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Review identity verification details</p>
                        </div>
                        <a href="{{ route('admin.kyc.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Back
                        </a>
                    </div>

                    @php
                        $statusColors = [
                            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                            'approved' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                            'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                        ];
                    @endphp

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden mb-6">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">User Information</h2>
                        </div>
                        <div class="p-5">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-gradient-to-br from-market-500 to-market-700 rounded-xl flex items-center justify-center shrink-0">
                                    <span class="text-white font-bold text-lg">{{ substr($kycVerification->user?->name ?? '?', 0, 1) }}</span>
                                </div>
                                <div>
                                    <p class="text-base font-semibold text-gray-900 dark:text-white">{{ $kycVerification->user?->name ?? 'Deleted User' }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $kycVerification->user?->email }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden mb-6">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Document Details</h2>
                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$kycVerification->status] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400' }}">
                                {{ ucfirst($kycVerification->status) }}
                            </span>
                        </div>
                        <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Document Type</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white capitalize">{{ str_replace('_', ' ', $kycVerification->document_type) }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Document Number</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $kycVerification->document_number }}</p>
                            </div>
                        </div>
                    </div>

                    @if($kycVerification->documentFrontUrl || $kycVerification->documentBackUrl)
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden mb-6">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Document Images</h2>
                            </div>
                            <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-6">
                                @if($kycVerification->documentFrontUrl)
                                    <div>
                                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Front</p>
                                        <a href="{{ $kycVerification->documentFrontUrl }}" target="_blank">
                                            <img src="{{ $kycVerification->documentFrontUrl }}" alt="Document Front" class="w-full rounded-xl border border-gray-200 dark:border-gray-600">
                                        </a>
                                    </div>
                                @endif
                                @if($kycVerification->documentBackUrl)
                                    <div>
                                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Back</p>
                                        <a href="{{ $kycVerification->documentBackUrl }}" target="_blank">
                                            <img src="{{ $kycVerification->documentBackUrl }}" alt="Document Back" class="w-full rounded-xl border border-gray-200 dark:border-gray-600">
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if($kycVerification->selfie)
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden mb-6">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Selfie</h2>
                            </div>
                            <div class="p-5">
                                <a href="{{ asset('storage/' . $kycVerification->selfie) }}" target="_blank">
                                    <img src="{{ asset('storage/' . $kycVerification->selfie) }}" alt="Selfie" class="max-w-sm rounded-xl border border-gray-200 dark:border-gray-600">
                                </a>
                            </div>
                        </div>
                    @endif

                    @if($kycVerification->admin_notes)
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden mb-6">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Admin Notes</h2>
                            </div>
                            <div class="p-5">
                                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $kycVerification->admin_notes }}</p>
                                @if($kycVerification->verifier)
                                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Reviewed by {{ $kycVerification->verifier->name }} on {{ $kycVerification->verified_at?->format('M d, Y h:i A') }}</p>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if($kycVerification->status === 'pending')
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden mb-6">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Rejection Notes</h2>
                            </div>
                            <div class="p-5">
                                <form method="POST" action="{{ route('admin.kyc.reject', $kycVerification) }}">
                                    @csrf
                                    <div>
                                        <x-input-label for="admin_notes" value="Reason for Rejection *" />
                                        <textarea id="admin_notes" name="admin_notes" rows="3" required
                                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm">{{ old('admin_notes') }}</textarea>
                                        <x-input-error :messages="$errors->get('admin_notes')" class="mt-1" />
                                    </div>
                                    <div class="mt-4 flex items-center gap-3">
                                        <form method="POST" action="{{ route('admin.kyc.approve', $kycVerification) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-xl transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                Approve
                                            </button>
                                        </form>
                                        <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-xl transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            Reject
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
