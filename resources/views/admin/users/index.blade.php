<x-app-layout>
    @section('title', 'Users')
    <section class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex gap-8">
                <x-admin-sidebar />
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Users</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage all registered users</p>
                        </div>
                        <form method="GET" class="flex items-center gap-2">
                            <select name="role" onchange="this.form.submit()" class="rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm focus:border-market-500 focus:ring-market-500">
                                <option value="">All Roles</option>
                                <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                                <option value="seller" {{ request('role') === 'seller' ? 'selected' : '' }}>Seller</option>
                                <option value="supplier" {{ request('role') === 'supplier' ? 'selected' : '' }}>Supplier</option>
                                <option value="customer" {{ request('role') === 'customer' ? 'selected' : '' }}>Customer</option>
                            </select>
                            <select name="status" onchange="this.form.submit()" class="rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm focus:border-market-500 focus:ring-market-500">
                                <option value="">All Status</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                            </select>
                            <input type="text" name="search" placeholder="Search name/email..." value="{{ request('search') }}" class="rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm focus:border-market-500 focus:ring-market-500 w-48">
                            <button type="submit" hidden></button>
                        </form>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-50 dark:bg-gray-800/50">
                                        <th class="px-5 py-3">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'dir' => ($sortField === 'name' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-gray-900 dark:hover:text-white">User</a>
                                        </th>
                                        <th class="px-5 py-3">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'email', 'dir' => ($sortField === 'email' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-gray-900 dark:hover:text-white">Email</a>
                                        </th>
                                        <th class="px-5 py-3">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'role_type', 'dir' => ($sortField === 'role_type' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-gray-900 dark:hover:text-white">Role</a>
                                        </th>
                                        <th class="px-5 py-3 text-center">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'status', 'dir' => ($sortField === 'status' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" class="flex items-center justify-center gap-1 hover:text-gray-900 dark:hover:text-white">Status</a>
                                        </th>
                                        <th class="px-5 py-3 text-right">Verified</th>
                                        <th class="px-5 py-3 text-right">
                                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'dir' => ($sortField === 'created_at' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" class="flex items-center justify-end gap-1 hover:text-gray-900 dark:hover:text-white">Joined</a>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse($users as $user)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                            <td class="px-5 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-9 h-9 rounded-full bg-gradient-to-br from-market-500 to-market-700 flex items-center justify-center text-white font-bold text-sm shrink-0">{{ substr($user->name, 0, 1) }}</div>
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->name }}</p>
                                                        <p class="text-xs text-gray-500">{{ '@'.$user->username }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</td>
                                            <td class="px-5 py-4">
                                                <x-inline-edit model="User" :id="$user->id" field="role_type" :value="$user->role_type" type="select" :options="['admin' => 'Admin', 'seller' => 'Seller', 'supplier' => 'Supplier', 'customer' => 'Customer']" />
                                            </td>
                                            <td class="px-5 py-4 text-center">
                                                <x-inline-edit model="User" :id="$user->id" field="status" :value="$user->status" type="select" :options="['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended']" />
                                            </td>
                                            <td class="px-5 py-4 text-sm text-right">
                                                @if($user->email_verified_at)
                                                    <span class="text-green-600 dark:text-green-400 font-medium">Yes</span>
                                                @else
                                                    <span class="text-red-600 dark:text-red-400 font-medium">No</span>
                                                @endif
                                            </td>
                                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400 text-right">{{ $user->created_at->format('M d, Y') }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="px-5 py-12 text-center text-sm text-gray-500">No users found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($users->hasPages())<div class="px-5 py-3 border-t border-gray-100 dark:border-gray-700">{{ $users->links() }}</div>@endif
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
