<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);
const mobileNavOpen = ref(false);

const navItems = [
    { label: 'Dashboard', href: '/admin/dashboard' },
    { label: 'Bookings', href: '/admin/bookings' },
    { label: 'Reports', href: '/admin/reports' },
    { label: 'Admins', href: '/admin/admins' },
];

function isActive(href) {
    return page.url.startsWith(href);
}

function logout() {
    router.post('/admin/logout');
}
</script>

<template>
    <div class="min-h-screen flex bg-chalk-50">
        <!-- Desktop sidebar -->
        <aside class="hidden md:flex md:flex-col w-60 bg-pitch-900 text-chalk-50 shrink-0">
            <div class="px-5 py-5 flex items-center gap-2.5 border-b border-chalk-50/10">
                <span class="w-8 h-8 rounded-full bg-floodlight-500 flex items-center justify-center font-display font-bold text-pitch-900 text-sm">Z</span>
                <span class="font-display font-semibold uppercase text-sm tracking-wide">Ground Admin</span>
            </div>
            <nav class="flex-1 px-3 py-4 space-y-1">
                <Link
                    v-for="item in navItems"
                    :key="item.href"
                    :href="item.href"
                    class="block px-3 py-2.5 rounded-md text-sm font-medium transition-colors"
                    :class="isActive(item.href) ? 'bg-chalk-50/10 text-chalk-50' : 'text-chalk-50/70 hover:bg-chalk-50/5 hover:text-chalk-50'"
                >
                    {{ item.label }}
                </Link>
            </nav>
            <div class="px-3 py-4 border-t border-chalk-50/10">
                <button @click="logout" class="w-full text-left px-3 py-2.5 rounded-md text-sm font-medium text-chalk-50/70 hover:bg-chalk-50/5 hover:text-chalk-50">
                    Log out
                </button>
            </div>
        </aside>

        <div class="flex-1 min-w-0 flex flex-col">
            <!-- Mobile top bar -->
            <header class="md:hidden bg-pitch-900 text-chalk-50 px-4 py-3.5 flex items-center justify-between">
                <span class="font-display font-semibold uppercase text-sm tracking-wide">Ground Admin</span>
                <button @click="mobileNavOpen = !mobileNavOpen" class="w-9 h-9 flex items-center justify-center" aria-label="Toggle menu">
                    <span class="text-xl">&#9776;</span>
                </button>
            </header>
            <nav v-if="mobileNavOpen" class="md:hidden bg-pitch-900 text-chalk-50 px-3 pb-3 space-y-1">
                <Link
                    v-for="item in navItems"
                    :key="item.href"
                    :href="item.href"
                    class="block px-3 py-2.5 rounded-md text-sm font-medium"
                    :class="isActive(item.href) ? 'bg-chalk-50/10' : 'text-chalk-50/70'"
                    @click="mobileNavOpen = false"
                >
                    {{ item.label }}
                </Link>
                <button @click="logout" class="w-full text-left px-3 py-2.5 rounded-md text-sm font-medium text-chalk-50/70">
                    Log out
                </button>
            </nav>

            <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-6xl w-full mx-auto">
                <div v-if="flashSuccess" class="mb-4 rounded-md bg-pitch-100 border border-pitch-400/30 text-pitch-900 px-4 py-3 text-sm">
                    {{ flashSuccess }}
                </div>
                <div v-if="flashError" class="mb-4 rounded-md bg-clay-500/10 border border-clay-500/30 text-clay-600 px-4 py-3 text-sm">
                    {{ flashError }}
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>
