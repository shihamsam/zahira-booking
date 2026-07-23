<script setup>
import { Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
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

// ── Navigation ────────────────────────────────────────────────────────────────

const pickedDate = ref(props.weekStart);
watch(() => props.weekStart, val => { pickedDate.value = val; });

function nav(week) {
    router.get('/admin/calendar', { week }, { preserveState: true, replace: true });
}
function today() {
    router.get('/admin/calendar', {}, { preserveState: true, replace: true });
}
function jumpToDate() {
    if (pickedDate.value) nav(pickedDate.value);
}

function fmtRange(start, end) {
    const s = new Date(start + 'T00:00:00');
    const e = new Date(end   + 'T00:00:00');
    return s.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' })
        + ' – '
        + e.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

// ── Time helpers ──────────────────────────────────────────────────────────────

// Night period in display order: 6 PM → midnight → 6 AM
const HOURS = [18, 19, 20, 21, 22, 23, 0, 1, 2, 3, 4, 5];

function hourLabel(h) {
    const norm = ((h % 24) + 24) % 24;
    if (norm === 0)  return '12 AM';
    if (norm === 12) return '12 PM';
    return norm < 12 ? `${norm} AM` : `${norm - 12} PM`;
}

// Midnight divide: rows 18-23 are evening, 0-5 are early morning.
function isMidnightOrAfter(h) {
    return h < 6;
}

// ── Per-day, per-hour lookup map ──────────────────────────────────────────────

// For each day, build hour→slots[] map (0–23 using slot_hour % 24).
const nightMaps = computed(() =>
    props.days.map(day => {
        const map = {};
        for (let h = 0; h < 24; h++) map[h] = [];
        for (const slot of (day.night_slots ?? [])) {
            const h = slot.slot_hour % 24;
            map[h].push(slot);
        }
        return map;
    })
);

// ── Slot labels ───────────────────────────────────────────────────────────────

const SLOT_LABEL = {
    full_day:     'Full Day',
    daytime:      'Daytime',
    night_4lights:'4 Lights',
    night_2lights:'2 Lights',
};

function statusClass(status) {
    return status === 'confirmed'
        ? 'bg-pitch-600/10 text-pitch-700 border-pitch-600/25'
        : 'bg-floodlight-500/15 text-floodlight-700 border-floodlight-500/30';
}
</script>

<template>
    <AdminLayout>

        <!-- ── Page header ────────────────────────────────────────────────── -->
        <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
            <h1 class="font-display font-bold text-2xl text-pitch-900">
                Calendar
                <span class="font-normal text-base text-ink-700/60 ml-2">{{ fmtRange(weekStart, weekEnd) }}</span>
            </h1>
            <div class="flex items-center gap-2 flex-wrap">
                <label class="flex items-center gap-2 bg-white border border-chalk-200 rounded-md px-3 h-9 text-sm text-ink-700/60 shadow-sm">
                    <span class="font-medium text-ink-700 whitespace-nowrap">Go to</span>
                    <input
                        v-model="pickedDate"
                        type="date"
                        @change="jumpToDate"
                        class="bg-transparent border-none outline-none text-ink-900 text-sm w-36 cursor-pointer"
                    />
                </label>
                <div class="w-px h-5 bg-chalk-200"></div>
                <button @click="nav(prevWeek)" class="btn-outline text-xs px-3 py-1.5">&larr; Prev</button>
                <button @click="today()" class="btn-outline text-xs px-3 py-1.5">Today</button>
                <button @click="nav(nextWeek)" class="btn-outline text-xs px-3 py-1.5">Next &rarr;</button>
            </div>
        </div>

        <!-- ── Calendar grid ─────────────────────────────────────────────── -->
        <div class="border border-chalk-200 rounded-card overflow-hidden bg-white">
            <div class="overflow-auto max-h-[calc(100vh-14rem)]">

                <!-- Sticky column headers -->
                <div class="grid sticky top-0 z-20 bg-chalk-100 border-b border-chalk-200"
                     :style="{ gridTemplateColumns: '4rem repeat(7, 1fr)' }">
                    <!-- Time gutter -->
                    <div class="border-r border-chalk-200 py-2 px-1 text-center">
                        <span class="text-[10px] font-mono uppercase tracking-wide text-ink-700/40">Time</span>
                    </div>
                    <!-- Day columns -->
                    <div
                        v-for="day in days"
                        :key="day.date + '-hdr'"
                        class="py-2 px-2 text-center border-r border-chalk-200 last:border-r-0"
                        :class="day.is_today ? 'bg-pitch-900 text-chalk-50' : ''"
                    >
                        <p class="text-[10px] font-semibold uppercase tracking-wide opacity-60">{{ day.label.split(' ')[0] }}</p>
                        <p class="font-display font-bold text-lg leading-none" :class="day.is_today ? '' : 'text-pitch-900'">
                            {{ day.label.split(' ')[1] }}
                        </p>
                    </div>
                </div>

                <!-- All-day row: daytime, full_day, blocked dates -->
                <div class="grid border-b-2 border-chalk-200 bg-chalk-50"
                     :style="{ gridTemplateColumns: '4rem repeat(7, 1fr)' }">
                    <div class="border-r border-chalk-200 px-1 py-1.5 flex items-center justify-center">
                        <span class="text-[9px] font-mono uppercase tracking-widest text-ink-700/40">Day Time</span>
                    </div>
                    <div
                        v-for="day in days"
                        :key="day.date + '-allday'"
                        class="border-r border-chalk-200 last:border-r-0 px-1.5 py-1.5 space-y-1 min-h-[3rem]"
                        :class="day.is_today ? 'bg-pitch-50/50' : ''"
                    >
                        <!-- Blocked -->
                        <div
                            v-for="block in day.blocked"
                            :key="block.id"
                            class="rounded text-[10px] px-1.5 py-0.5 bg-ink-700/8 text-ink-700/60 border border-dashed border-ink-700/20 truncate"
                            :title="block.resource ?? 'All facilities'"
                        >
                            🚫 {{ block.resource ?? 'All' }}
                        </div>
                        <!-- Daytime / Full-day bookings -->
                        <Link
                            v-for="b in day.day_bookings"
                            :key="b.id"
                            :href="`/admin/bookings/${b.id}`"
                            class="block rounded text-[10px] px-1.5 py-0.5 border truncate leading-tight"
                            :class="statusClass(b.status)"
                            :title="`${b.reference_no} — ${b.full_name} (${SLOT_LABEL[b.slot_type] ?? b.slot_type})`"
                        >
                            <span class="font-mono font-semibold">{{ b.reference_no.split('-').pop() }}</span>
                            <span class="opacity-60 ml-1">{{ b.shortcode ?? b.resource }}</span>
                        </Link>
                    </div>
                </div>

                <!-- Night hourly rows: 6 PM → 6 AM -->
                <div
                    v-for="hour in HOURS"
                    :key="hour"
                    class="grid border-b border-chalk-100 last:border-b-0"
                    :style="{ gridTemplateColumns: '4rem repeat(7, 1fr)' }"
                    :class="isMidnightOrAfter(hour) ? 'bg-chalk-50/40' : ''"
                >
                    <!-- Time label -->
                    <div class="border-r border-chalk-200 flex items-start justify-end pr-2 pt-0.5 h-9 text-ink-700/50">
                        <span class="text-[10px] font-mono leading-none">{{ hourLabel(hour) }}</span>
                    </div>

                    <!-- Day cells -->
                    <div
                        v-for="(day, di) in days"
                        :key="day.date + '-' + hour"
                        class="border-r border-chalk-100 last:border-r-0 h-9 px-1 py-0.5 relative"
                        :class="day.is_today ? 'bg-pitch-50/30' : ''"
                    >
                        <Link
                            v-for="slot in nightMaps[di][hour]"
                            :key="`${slot.id}-${slot.slot_hour}`"
                            :href="`/admin/bookings/${slot.id}`"
                            class="flex items-center gap-1 rounded text-[10px] px-1.5 py-0.5 border truncate leading-tight h-full"
                            :class="statusClass(slot.status)"
                            :title="`${slot.reference_no} — ${slot.full_name} (${SLOT_LABEL[slot.slot_type] ?? slot.slot_type})`"
                        >
                            <span class="font-mono font-semibold shrink-0">{{ slot.reference_no.split('-').pop() }}</span>
                            <span class="opacity-60 truncate">{{ slot.shortcode ?? slot.resource }}</span>
                        </Link>
                    </div>
                </div>

            </div>
        </div>

        <!-- Legend -->
        <div class="mt-3 flex flex-wrap items-center gap-4 text-xs text-ink-700/60">
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-sm bg-floodlight-500/30 border border-floodlight-500/40"></span> Pending
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-sm bg-pitch-600/15 border border-pitch-600/25"></span> Confirmed
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-sm bg-ink-700/10 border border-dashed border-ink-700/30"></span> Blocked
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-sm bg-chalk-50 border border-chalk-200"></span> After midnight (12 AM – 6 AM)
            </span>
        </div>

    </AdminLayout>
</template>
