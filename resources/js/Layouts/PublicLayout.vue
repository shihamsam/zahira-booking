<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex flex-col">
        <header class="bg-pitch-900 text-chalk-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
                <Link href="/" class="flex items-center gap-3">
                    <img src="/images/logo.png" alt="Zahira College seal" class="h-12 w-12 object-contain shrink-0" />
                    <img src="/images/logo-text.png" alt="Zahira College" class="h-10 w-auto object-contain" />
                </Link>
                <div class="flex items-center gap-4">
                    <Link href="/upload-receipt" class="text-xs sm:text-sm text-chalk-50/70 hover:text-chalk-50 font-medium">
                        Upload Receipt
                    </Link>
                    <Link href="/admin/login" class="text-xs sm:text-sm text-chalk-50/70 hover:text-chalk-50 font-medium">
                        Admin
                    </Link>
                </div>
            </div>
        </header>

        <main class="flex-1 bg-chalk-50">
            <slot />
        </main>

        <footer class="bg-pitch-950 text-chalk-50/60 text-xs">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6">
                &copy; {{ new Date().getFullYear() }} Zahira College - Puttalam - Booking Portal
            </div>
        </footer>
    </div>
</template>
