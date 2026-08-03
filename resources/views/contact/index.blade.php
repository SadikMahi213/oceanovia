<x-app-layout>
    @section('title', 'Contact Us')

    <section class="relative bg-gradient-to-br from-market-600 via-market-700 to-indigo-900 py-12 lg:py-16">
        <div class="max-w-7xl mx-auto px-4 relative">
            <nav class="flex items-center gap-2 text-sm text-white/70 mb-4">
                <a href="{{ route('home') }}" class="hover:text-white transition-colors">Home</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-white font-medium">Contact Us</span>
            </nav>
            <h1 class="text-3xl lg:text-4xl font-bold text-white">Get in Touch</h1>
            <p class="text-market-200 mt-2 max-w-xl">Questions about an order, your account, or selling on MulitVendor? Send us a message.</p>
        </div>
    </section>

    <section class="py-8 lg:py-12 bg-white dark:bg-gray-950">
        <div class="max-w-3xl mx-auto px-4">
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 rounded-xl text-sm text-green-700 dark:text-green-300">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('contact.store') }}" class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl p-6 lg:p-8">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <x-input-label for="name" value="Your Name" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name') }}" required autocomplete="name" />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="email" value="Email Address" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" value="{{ old('email') }}" required autocomplete="email" />
                        <x-input-error :messages="$errors->get('email')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="phone" value="Phone (optional)" />
                        <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" value="{{ old('phone') }}" autocomplete="tel" />
                        <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="subject" value="Subject" />
                        <x-text-input id="subject" name="subject" type="text" class="mt-1 block w-full" value="{{ old('subject') }}" required />
                        <x-input-error :messages="$errors->get('subject')" class="mt-1" />
                    </div>
                </div>

                <div class="mt-5">
                    <x-input-label for="message" value="Message" />
                    <textarea id="message" name="message" rows="6" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-market-500 focus:ring-market-500 text-sm" required>{{ old('message') }}</textarea>
                    <x-input-error :messages="$errors->get('message')" class="mt-1" />
                </div>

                <div class="mt-6">
                    <button type="submit" class="px-6 py-3 bg-market-600 hover:bg-market-700 text-white text-sm font-medium rounded-xl transition-colors">
                        Send Message
                    </button>
                </div>
            </form>
        </div>
    </section>
</x-app-layout>
