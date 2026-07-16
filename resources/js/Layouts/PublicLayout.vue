<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex flex-col">
        <header class="bg-pitch-900 text-chalk-50">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
                <Link href="/" class="flex items-center gap-2.5">
                    <span class="w-9 h-9 rounded-full bg-floodlight-500 flex items-center justify-center font-display font-bold text-pitch-900">Z</span>
                    <span class="font-display font-semibold tracking-wide uppercase text-sm sm:text-base">Zahira Ground Booking</span>
                </Link>
                <Link href="/admin/login" class="text-xs sm:text-sm text-chalk-50/70 hover:text-chalk-50 font-medium">
                    Admin
                </Link>
            </div>
        </header>

        <main class="flex-1 bg-chalk-50">
            <slot />
        </main>

        <footer class="bg-pitch-950 text-chalk-50/60 text-xs">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 py-6">
                &copy; {{ new Date().getFullYear() }} Zahira College &mdash; Ground Booking
            </div>
        </footer>
    </div>
</template>
