<script setup>
import { useForm, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Calendar from '@/Components/Calendar.vue';

const props = defineProps({
    blockedDates:     { type: Array,  default: () => [] },
    resources:        { type: Array,  default: () => [] },
    bookedByResource: { type: Object, default: () => ({}) },
});

// ── Filter resource for the calendar view ─────────────────────────────────────
const calendarResourceId = ref('');

// Booked dates for the currently viewed resource scope.
const calendarBooked = computed(() => {
    if (!calendarResourceId.value) {
        return Object.values(props.bookedByResource).flat();
    }
    return props.bookedByResource[calendarResourceId.value] ?? [];
});

// Dates that cannot be selected: already-blocked dates PLUS dates with active bookings.
// Booked dates cannot be blocked, so they are greyed-out rather than shown in amber.
const calendarUnavailable = computed(() => {
    const rid = calendarResourceId.value ? Number(calendarResourceId.value) : null;
    const blocked = props.blockedDates
        .filter(b => b.resource_id === null || b.resource_id === rid)
        .map(b => b.date.slice(0, 10));
    return [...new Set([...blocked, ...calendarBooked.value])];
});

// ── Form ──────────────────────────────────────────────────────────────────────
const form = useForm({
    dates:       [],
    resource_id: '',
    reason:      '',
});

// Keep the calendar resource filter in sync with the form resource.
function onResourceChange() {
    calendarResourceId.value = form.resource_id;
    form.dates = [];
}

function submit() {
    form.post('/admin/blocked-dates', {
        preserveScroll: true,
        onSuccess: () => { form.dates = []; form.reason = ''; },
    });
}

function removeDate(d) {
    form.dates = form.dates.filter(x => x !== d);
}

// ── List actions ──────────────────────────────────────────────────────────────
function removeBlock(id) {
    router.delete(`/admin/blocked-dates/${id}`, { preserveScroll: true });
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function formatDate(d) {
    const [year, month, day] = d.slice(0, 10).split('-').map(Number);
    return new Date(year, month - 1, day).toLocaleDateString('en-GB', {
        weekday: 'short', day: 'numeric', month: 'short', year: 'numeric',
    });
}

function formatShort(d) {
    const [year, month, day] = d.slice(0, 10).split('-').map(Number);
    return new Date(year, month - 1, day).toLocaleDateString('en-GB', {
        day: 'numeric', month: 'short',
    });
}

// Blocked dates filtered to the list scope (all, or by resource)
const listResourceId = ref('');
const filteredList = computed(() => {
    if (!listResourceId.value) return props.blockedDates;
    const rid = Number(listResourceId.value);
    return props.blockedDates.filter(b => b.resource_id === rid || b.resource_id === null);
});
</script>

<template>
    <AdminLayout>
        <h1 class="font-display font-bold text-2xl text-pitch-900 mb-6">Blocked Dates</h1>

        <div class="grid lg:grid-cols-5 gap-6">

            <!-- ── Calendar panel ────────────────────────────────────────── -->
            <div class="lg:col-span-3 space-y-4">

                <!-- Resource selector for calendar view -->
                <div class="card p-4 flex items-center gap-3 flex-wrap">
                    <label class="text-sm font-medium text-ink-700 shrink-0">Viewing blocks for:</label>
                    <select
                        v-model="calendarResourceId"
                        @change="form.resource_id = calendarResourceId; form.dates = []"
                        class="field-input flex-1 min-w-[160px]"
                    >
                        <option value="">All facilities</option>
                        <option v-for="r in resources" :key="r.id" :value="String(r.id)">{{ r.name }}</option>
                    </select>
                    <p class="text-xs text-ink-700/50 w-full">
                        Greyed dates are already blocked or have active bookings — these cannot be blocked.
                    </p>
                </div>

                <!-- Calendar -->
                <Calendar
                    v-model="form.dates"
                    :unavailable-dates="calendarUnavailable"
                />
            </div>

            <!-- ── Sidebar: form + list ──────────────────────────────────── -->
            <div class="lg:col-span-2 space-y-5">

                <!-- Block form -->
                <div class="card p-5">
                    <h2 class="font-display font-semibold uppercase tracking-wide text-sm text-pitch-900 mb-4">
                        Block selected dates
                    </h2>

                    <!-- Selected date pills -->
                    <div v-if="form.dates.length" class="flex flex-wrap gap-1.5 mb-4">
                        <span
                            v-for="d in form.dates"
                            :key="d"
                            class="inline-flex items-center gap-1 bg-clay-500/10 text-clay-700 text-xs font-mono px-2.5 py-1 rounded-full border border-clay-500/20"
                        >
                            {{ formatShort(d) }}
                            <button type="button" @click="removeDate(d)" class="opacity-60 hover:opacity-100" aria-label="Remove">&times;</button>
                        </span>
                    </div>
                    <p v-else class="text-sm text-ink-700/40 mb-4 italic">No dates selected — click dates on the calendar.</p>

                    <form @submit.prevent="submit" class="space-y-3">
                        <div>
                            <label class="field-label">Facility <span class="text-ink-700/40 font-normal">(leave blank to block all)</span></label>
                            <select v-model="form.resource_id" @change="onResourceChange" class="field-input">
                                <option value="">All facilities</option>
                                <option v-for="r in resources" :key="r.id" :value="String(r.id)">{{ r.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="field-label">Reason <span class="text-ink-700/40 font-normal">(optional)</span></label>
                            <input v-model="form.reason" type="text" class="field-input" placeholder="e.g. College sports day" />
                        </div>
                        <p v-if="form.errors.dates" class="text-clay-600 text-xs">{{ form.errors.dates }}</p>
                        <button
                            type="submit"
                            class="btn-primary w-full"
                            :disabled="form.processing || form.dates.length === 0"
                        >
                            {{ form.processing ? 'Blocking...' : `Block ${form.dates.length || ''} date${form.dates.length === 1 ? '' : 's'}` }}
                        </button>
                    </form>
                </div>

                <!-- Blocked dates list -->
                <div class="card overflow-hidden">
                    <div class="px-5 py-4 border-b border-chalk-200 flex items-center justify-between gap-3 flex-wrap">
                        <h2 class="font-display font-semibold uppercase tracking-wide text-sm text-pitch-900">
                            Blocked ({{ filteredList.length }})
                        </h2>
                        <select v-model="listResourceId" class="field-input text-xs py-1 w-auto">
                            <option value="">All</option>
                            <option v-for="r in resources" :key="r.id" :value="String(r.id)">{{ r.name }}</option>
                        </select>
                    </div>

                    <div v-if="filteredList.length === 0" class="px-5 py-6 text-center text-ink-700/50 text-sm">
                        No blocked dates.
                    </div>

                    <ul class="divide-y divide-chalk-200 max-h-80 overflow-y-auto">
                        <li
                            v-for="block in filteredList"
                            :key="block.id"
                            class="px-5 py-3 flex items-start justify-between gap-3"
                        >
                            <div class="min-w-0">
                                <p class="font-medium text-sm">{{ formatDate(block.date) }}</p>
                                <p class="text-xs text-ink-700/50 truncate">
                                    {{ block.resource ? block.resource.name : 'All facilities' }}
                                    <span v-if="block.reason"> &middot; {{ block.reason }}</span>
                                </p>
                            </div>
                            <button
                                @click="removeBlock(block.id)"
                                class="text-clay-600 hover:text-clay-700 text-xs font-medium shrink-0 mt-0.5"
                            >
                                Remove
                            </button>
                        </li>
                    </ul>
                </div>

            </div>
        </div>
    </AdminLayout>
</template>
