<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);
const mobileNavOpen = ref(false);

const isSuperAdmin = computed(() => page.props.auth?.user?.isSuperAdmin ?? false);

const navItems = computed(() => [
    { label: 'Dashboard',     href: '/admin/dashboard' },
    { label: 'Bookings',      href: '/admin/bookings' },
    { label: 'Calendar',      href: '/admin/calendar' },
    { label: 'Blocked Dates', href: '/admin/blocked-dates' },
    { label: 'Facilities',    href: '/admin/resources' },
    { label: 'Reports',       href: '/admin/reports' },
    ...(isSuperAdmin.value ? [{ label: 'Admins', href: '/admin/admins' }] : []),
]);

function isActive(href) {
    return page.url.startsWith(href);
}

function logout() {
    router.post('/admin/logout');
}
</script>

<template>
    <div class="h-screen overflow-hidden flex bg-chalk-50">
        <!-- Desktop sidebar — fixed viewport height, never scrolls -->
        <aside class="hidden md:flex md:flex-col w-60 bg-pitch-900 text-chalk-50 shrink-0 h-full">
            <Link href="/admin/login" class="px-5 py-4 flex items-center gap-3 border-b border-chalk-50/10 hover:bg-chalk-50/5 transition-colors">
                <img src="/images/logo.png" alt="Zahira College seal" class="h-12 w-12 object-contain shrink-0" />
                <img src="/images/logo-text.png" alt="Zahira College" class="h-10 w-auto object-contain min-w-0" />
            </Link>
            <nav class="flex-1 px-3 py-4 space-y-1">
                <!-- Greeting -->
                <Link
                    href="/admin/profile"
                    class="block px-3 py-2 mb-2 text-sm font-semibold text-floodlight-400 hover:text-floodlight-300 transition-colors"
                >
                    Hello, {{ page.props.auth?.user?.name?.split(' ')[0] }}
                </Link>

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
            <div class="px-3 py-4 border-t border-chalk-50/10 space-y-1">
                <Link href="/" class="block px-3 py-2.5 rounded-md text-sm font-medium text-chalk-50/70 hover:bg-chalk-50/5 hover:text-chalk-50">
                    ← View site
                </Link>
                <button @click="logout" class="w-full text-left px-3 py-2.5 rounded-md text-sm font-medium text-chalk-50/70 hover:bg-chalk-50/5 hover:text-chalk-50">
                    Log out
                </button>
            </div>
        </aside>

        <div class="flex-1 min-w-0 flex flex-col h-full overflow-hidden">
            <!-- Mobile top bar -->
            <header class="md:hidden bg-pitch-900 text-chalk-50 px-4 py-3.5 flex items-center justify-between">
                <Link href="/admin/login" class="flex items-center gap-2.5">
                    <img src="/images/logo.png" alt="Zahira College seal" class="h-10 w-10 object-contain shrink-0" />
                    <img src="/images/logo-text.png" alt="Zahira College" class="h-9 w-auto object-contain" />
                </Link>
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
                <Link href="/admin/profile" class="block px-3 py-2.5 rounded-md text-sm font-medium text-chalk-50/70" @click="mobileNavOpen = false">
                    My Account
                </Link>
                <button @click="logout" class="w-full text-left px-3 py-2.5 rounded-md text-sm font-medium text-chalk-50/70">
                    Log out
                </button>
            </nav>

            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 max-w-6xl w-full mx-auto">
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
