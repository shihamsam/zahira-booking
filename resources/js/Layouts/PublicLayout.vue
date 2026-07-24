<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const supportPhone  = computed(() => usePage().props.supportPhone  ?? '');
const supportPhone2 = computed(() => usePage().props.supportPhone2 ?? '');

function formatPhone(raw) {
    const d = raw.replace(/\D/g, '');
    if (d.length === 10) return `${d.slice(0, 3)} ${d.slice(3, 6)} ${d.slice(6)}`;
    return raw;
}

const displayPhone  = computed(() => formatPhone(supportPhone.value));
const displayPhone2 = computed(() => formatPhone(supportPhone2.value));
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
                    <template v-if="supportPhone || supportPhone2">
                        <div class="hidden sm:flex items-center gap-1.5 text-xs text-chalk-50/70">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 shrink-0 text-chalk-50/50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            <span class="text-chalk-50/50">Call us for support</span>
                            <a v-if="supportPhone"  :href="`tel:${supportPhone.replace(/\D/g,'')}`"  class="font-mono font-semibold tracking-wide hover:text-chalk-50 transition-colors">{{ displayPhone }}</a>
                            <span v-if="supportPhone && supportPhone2" class="text-chalk-50/30">/</span>
                            <a v-if="supportPhone2" :href="`tel:${supportPhone2.replace(/\D/g,'')}`" class="font-mono font-semibold tracking-wide hover:text-chalk-50 transition-colors">{{ displayPhone2 }}</a>
                        </div>
                    </template>
                    <Link href="/upload-receipt" class="text-xs sm:text-sm text-chalk-50/70 hover:text-chalk-50 font-medium">
                        Upload Receipt
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
