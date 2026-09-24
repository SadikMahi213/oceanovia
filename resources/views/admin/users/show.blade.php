<x-app-layout>
    @section('title', 'User Details')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-admin-sidebar />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-market-600 dark:hover:text-market-400">&larr; Back to users</a>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">User Details</h1>
                        </div>
                        @if($user->id !== auth()->id())
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete user {{ addslashes($user->full_name) }} ({{ addslashes($user->email) }})?\n\nTheir account will be disabled. Orders, payouts, KYC and history records are preserved.')">
                                @csrf
                                @method('DELETE')
                                <x-danger-button onclick="this.disabled=true; this.innerHTML='Deleting…'">{{ __('Delete User') }}</x-danger-button>
                            </form>
                        @endif
                    </div>

                    {{-- Identity card --}}
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 mb-6">
                        <div class="flex items-center gap-5">
                            <div class="relative shrink-0">
                                <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-20 h-20 rounded-full object-cover ring-4 ring-market-50 dark:ring-market-900/20">
                            </div>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-3">
                                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $user->full_name }}</h2>
                                    @if($user->email_verified_at)
                                        <span class="inline-flex items-center gap-1 text-xs font-medium text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 px-2.5 py-0.5 rounded-full">Verified</span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2.5 py-0.5 rounded-full">Unverified</span>
                                    @endif
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">@<span>{{ $user->username }}</span></p>
                                <div class="flex items-center gap-3 mt-3 text-xs">
                                    <span>
                                        <x-inline-edit model="User" :id="$user->id" field="role_type" :value="$user->role_type" type="select" :options="['admin' => 'Admin', 'seller' => 'Seller', 'supplier' => 'Supplier', 'customer' => 'Customer']" />
                                    </span>
                                    <span>
                                        <x-inline-edit model="User" :id="$user->id" field="status" :value="$user->status" type="select" :options="['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended']" />
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {{-- Account information --}}
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Account</h3>
                            </div>
                            <dl class="divide-y divide-gray-100 dark:divide-gray-700">
                                <div class="px-5 py-3 flex items-center justify-between gap-4">
                                    <dt class="text-sm text-gray-500 dark:text-gray-400">ID</dt>
                                    <dd class="text-sm font-medium text-gray-900 dark:text-white">#{{ $user->id }}</dd>
                                </div>
                                <div class="px-5 py-3 flex items-center justify-between gap-4">
                                    <dt class="text-sm text-gray-500 dark:text-gray-400">Joined</dt>
                                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->created_at->format('M d, Y H:i') }}</dd>
                                </div>
                                <div class="px-5 py-3 flex items-center justify-between gap-4">
                                    <dt class="text-sm text-gray-500 dark:text-gray-400">Last login</dt>
                                    <dd class="text-sm font-medium text-gray-900 dark:text-white text-right">{{ $user->last_login_at ? $user->last_login_at->format('M d, Y H:i') : 'Never' }}</dd>
                                </div>
                                <div class="px-5 py-3 flex items-center justify-between gap-4">
                                    <dt class="text-sm text-gray-500 dark:text-gray-400">Last login IP</dt>
                                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->last_login_ip ?? '—' }}</dd>
                                </div>
                            </dl>
                        </div>

                        {{-- Contact information --}}
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Contact</h3>
                            </div>
                            <dl class="divide-y divide-gray-100 dark:divide-gray-700">
                                <div class="px-5 py-3 flex items-center justify-between gap-4">
                                    <dt class="text-sm text-gray-500 dark:text-gray-400">Email</dt>
                                    <dd class="text-sm font-medium text-gray-900 dark:text-white break-all text-right">{{ $user->email }}</dd>
                                </div>
                                <div class="px-5 py-3 flex items-center justify-between gap-4">
                                    <dt class="text-sm text-gray-500 dark:text-gray-400">Phone</dt>
                                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->phone ?? '—' }}</dd>
                                </div>
                                <div class="px-5 py-3 flex items-center justify-between gap-4">
                                    <dt class="text-sm text-gray-500 dark:text-gray-400">Country</dt>
                                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->country ?? '—' }}</dd>
                                </div>
                                <div class="px-5 py-3 flex items-center justify-between gap-4">
                                    <dt class="text-sm text-gray-500 dark:text-gray-400">State</dt>
                                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->state ?? '—' }}</dd>
                                </div>
                                <div class="px-5 py-3 flex items-center justify-between gap-4">
                                    <dt class="text-sm text-gray-500 dark:text-gray-400">City</dt>
                                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->city ?? '—' }}</dd>
                                </div>
                                <div class="px-5 py-3 flex items-center justify-between gap-4">
                                    <dt class="text-sm text-gray-500 dark:text-gray-400">Postal code</dt>
                                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->postal_code ?? '—' }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div class="space-y-6">
                            {{-- Personal information --}}
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                                <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700">
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Personal</h3>
                                </div>
                                <dl class="divide-y divide-gray-100 dark:divide-gray-700">
                                    <div class="px-5 py-3 flex items-center justify-between gap-4">
                                        <dt class="text-sm text-gray-500 dark:text-gray-400">Date of birth</dt>
                                        <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->date_of_birth ? $user->date_of_birth->format('M d, Y') : '—' }}</dd>
                                    </div>
                                    <div class="px-5 py-3 flex items-center justify-between gap-4">
                                        <dt class="text-sm text-gray-500 dark:text-gray-400">Gender</dt>
                                        <dd class="text-sm font-medium text-gray-900 dark:text-white capitalize">{{ $user->gender ?? '—' }}</dd>
                                    </div>
                                    @php
                                        $prefs = collect($user->notification_preferences ?? [])
                                            ->map(fn ($enabled, $key) => is_numeric($key) ? $enabled : ((int) $enabled === 1 ? $key : null))
                                            ->filter()
                                            ->map(fn ($k) => ucwords(str_replace('_', ' ', (string) $k)))
                                            ->implode(', ');
                                    @endphp
                                    <div class="px-5 py-3 flex items-center justify-between gap-4">
                                        <dt class="text-sm text-gray-500 dark:text-gray-400">Notifications</dt>
                                        <dd class="text-sm font-medium text-gray-900 dark:text-white text-right">
                                            {{ $prefs !== '' ? $prefs : 'Default' }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            {{-- Cover image --}}
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                                <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700">
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Cover image</h3>
                                </div>
                                <div class="p-5">
                                    @if($user->cover_image)
                                        <img src="{{ asset('storage/'.$user->cover_image) }}" alt="Cover" class="w-full h-32 object-cover rounded-xl">
                                    @else
                                        <p class="text-sm text-gray-400">No cover image uploaded.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>