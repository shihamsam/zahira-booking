<script setup>
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
    days:      { type: Array,  required: true },
    weekStart: { type: String, required: true },
    weekEnd:   { type: String, required: true },
    prevWeek:  { type: String, required: true },
    nextWeek:  { type: String, required: true },
    resources: { type: Array,  default: () => [] },
});

const SLOT_SHORT = {
    full_day:     'Full day',
    daytime:      'Daytime',
    night_4lights:'Night 4L',
    night_2lights:'Night 2L',
};

function nav(week) {
    router.get('/admin/calendar', { week }, { preserveState: true, replace: true });
}

function today() {
    router.get('/admin/calendar', {}, { preserveState: true, replace: true });
}

function fmtRange(start, end) {
    const s = new Date(start + 'T00:00:00');
    const e = new Date(end   + 'T00:00:00');
    return s.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' })
        + ' – '
        + e.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}
</script>

<template>
    <AdminLayout>
        <!-- Header -->
        <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
            <h1 class="font-display font-bold text-2xl text-pitch-900">
                Calendar
                <span class="font-normal text-base text-ink-700/60 ml-2">{{ fmtRange(weekStart, weekEnd) }}</span>
            </h1>
            <div class="flex items-center gap-2">
                <button @click="nav(prevWeek)" class="btn-outline text-xs px-3 py-1.5">&larr; Prev</button>
                <button @click="today()" class="btn-outline text-xs px-3 py-1.5">Today</button>
                <button @click="nav(nextWeek)" class="btn-outline text-xs px-3 py-1.5">Next &rarr;</button>
            </div>
        </div>

        <!-- Week grid -->
        <div class="grid grid-cols-7 gap-px bg-chalk-200 rounded-card overflow-hidden border border-chalk-200">
            <!-- Day headers -->
            <div
                v-for="day in days"
                :key="day.date + '-hdr'"
                class="bg-chalk-100 px-2 py-2 text-center"
                :class="day.is_today ? 'bg-pitch-900 text-chalk-50' : ''"
            >
                <p class="text-xs font-semibold uppercase tracking-wide">{{ day.label.split(' ')[0] }}</p>
                <p class="font-display font-bold text-lg leading-none" :class="day.is_today ? '' : 'text-pitch-900'">
                    {{ day.label.split(' ')[1] }}
                </p>
            </div>

            <!-- Day cells -->
            <div
                v-for="day in days"
                :key="day.date"
                class="bg-white min-h-[140px] p-2 space-y-1.5"
                :class="day.is_today ? 'ring-2 ring-inset ring-pitch-900' : ''"
            >
                <!-- Blocked indicators -->
                <div
                    v-for="block in day.blocked"
                    :key="block.id"
                    class="rounded px-2 py-1 text-xs bg-ink-700/10 text-ink-700/70 border border-dashed border-ink-700/20"
                >
                    🚫 {{ block.resource ?? 'All facilities' }}
                    <span v-if="block.reason" class="block opacity-60 truncate">{{ block.reason }}</span>
                </div>

                <!-- Bookings -->
                <Link
                    v-for="b in day.bookings"
                    :key="b.id"
                    :href="`/admin/bookings/${b.id}`"
                    class="block rounded px-2 py-1 text-xs font-medium truncate border"
                    :class="{
                        'bg-floodlight-500/15 text-floodlight-600 border-floodlight-500/30': b.status === 'pending',
                        'bg-pitch-600/10 text-pitch-700 border-pitch-600/20': b.status === 'confirmed',
                    }"
                    :title="`${b.reference_no} — ${b.full_name}`"
                >
                    <span class="font-mono">{{ b.reference_no.split('-').pop() }}</span>
                    <span class="opacity-60 ml-1">{{ b.resource }}</span>
                    <span v-if="b.slot_type" class="ml-1 opacity-50">({{ SLOT_SHORT[b.slot_type] ?? b.slot_type }})</span>
                </Link>

                <p v-if="day.bookings.length === 0 && day.blocked.length === 0" class="text-xs text-ink-700/25 text-center pt-4">
                    Free
                </p>
            </div>
        </div>

        <!-- Legend -->
        <div class="mt-4 flex flex-wrap items-center gap-4 text-xs text-ink-700/60">
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-sm bg-floodlight-500/30 border border-floodlight-500/40"></span> Pending
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-sm bg-pitch-600/15 border border-pitch-600/25"></span> Confirmed
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-sm bg-ink-700/10 border border-dashed border-ink-700/30"></span> Blocked
            </span>
        </div>
    </AdminLayout>
</template>
