<script setup>
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

defineProps({
    stats: { type: Object, required: true },
    upcoming: { type: Array, default: () => [] },
    recentPending: { type: Array, default: () => [] },
});

function formatDate(d) {
    return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
}
</script>

<template>
    <AdminLayout>
        <h1 class="font-display font-bold text-2xl text-pitch-900 mb-6">Dashboard</h1>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="card p-5">
                <p class="text-xs uppercase tracking-wide text-ink-700/60 mb-1.5">Pending confirmations</p>
                <p class="font-display font-bold text-2xl text-floodlight-600">{{ stats.pending_count }}</p>
            </div>
            <div class="card p-5">
                <p class="text-xs uppercase tracking-wide text-ink-700/60 mb-1.5">Confirmed this month</p>
                <p class="font-display font-bold text-2xl text-pitch-900">{{ stats.confirmed_this_month }}</p>
            </div>
            <div class="card p-5">
                <p class="text-xs uppercase tracking-wide text-ink-700/60 mb-1.5">Income this month</p>
                <p class="font-display font-bold text-2xl text-pitch-900">Rs. {{ Number(stats.income_this_month).toLocaleString() }}</p>
            </div>
            <div class="card p-5">
                <p class="text-xs uppercase tracking-wide text-ink-700/60 mb-1.5">Total bookings</p>
                <p class="font-display font-bold text-2xl text-pitch-900">{{ stats.total_bookings }}</p>
            </div>
        </div>

        <div class="grid lg:grid-cols-2 gap-6">
            <div class="card p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-display font-semibold uppercase tracking-wide text-sm text-pitch-900">Needs confirmation</h2>
                    <Link href="/admin/bookings?status=pending" class="text-xs text-pitch-600 font-medium">View all</Link>
                </div>
                <ul class="space-y-3">
                    <li v-for="b in recentPending" :key="b.id" class="flex items-center justify-between">
                        <div>
                            <Link :href="`/admin/bookings/${b.id}`" class="font-medium text-sm text-ink-900 hover:text-pitch-600">
                                {{ b.reference_no }}
                            </Link>
                            <p class="text-xs text-ink-700/60">{{ b.full_name }} &middot; {{ b.resource.name }}</p>
                        </div>
                        <StatusBadge :status="b.status" />
                    </li>
                    <li v-if="recentPending.length === 0" class="text-sm text-ink-700/50">Nothing waiting on confirmation.</li>
                </ul>
            </div>

            <div class="card p-5">
                <h2 class="font-display font-semibold uppercase tracking-wide text-sm text-pitch-900 mb-4">Upcoming bookings</h2>
                <ul class="space-y-3">
                    <li v-for="b in upcoming" :key="b.id" class="flex items-center justify-between">
                        <div>
                            <Link :href="`/admin/bookings/${b.id}`" class="font-medium text-sm text-ink-900 hover:text-pitch-600">
                                {{ b.reference_no }}
                            </Link>
                            <p class="text-xs text-ink-700/60">
                                {{ b.resource.name }} &middot;
                                {{ b.dates.length ? formatDate(b.dates[0].date) : '' }}
                            </p>
                        </div>
                        <StatusBadge :status="b.status" />
                    </li>
                    <li v-if="upcoming.length === 0" class="text-sm text-ink-700/50">No upcoming bookings.</li>
                </ul>
            </div>
        </div>
    </AdminLayout>
</template>
