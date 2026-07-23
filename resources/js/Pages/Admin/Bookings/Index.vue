<script setup>
import { Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
    bookings: { type: Object, required: true },
    resources: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const resourceId = ref(props.filters.resource_id ?? '');
const from = ref(props.filters.from ?? '');
const to = ref(props.filters.to ?? '');

function applyFilters() {
    router.get('/admin/bookings', {
        search: search.value || undefined,
        status: status.value || undefined,
        resource_id: resourceId.value || undefined,
        from: from.value || undefined,
        to: to.value || undefined,
    }, { preserveState: true, replace: true });
}

const hasFilters = computed(() =>
    search.value || status.value || resourceId.value || from.value || to.value
);

function resetFilters() {
    search.value     = '';
    status.value     = '';
    resourceId.value = '';
    from.value       = '';
    to.value         = '';
    router.get('/admin/bookings', {}, { preserveState: true, replace: true });
}

let debounceTimer;
watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(applyFilters, 350);
});

function formatDate(d) {
    return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

// Deduplicate booking_dates rows by date value (night bookings have one row per hour).
function uniqueDates(dates) {
    return [...new Map(dates.map(d => [d.date, d])).values()];
}

// Convert a slot_hour (18-29 scale) to a "9:00 PM – 10:00 PM" label.
function hourLabel(h) {
    const norm = ((h % 24) + 24) % 24;
    const ampm = norm < 12 ? 'AM' : 'PM';
    const disp = norm % 12 === 0 ? 12 : norm % 12;
    return `${disp}:00 ${ampm}`;
}

// Returns an array of pill labels for the booking's slot selection.
function slotLabels(b) {
    const nightHours = [...new Set((b.dates ?? [])
        .filter(d => d.slot_hour !== null)
        .map(d => d.slot_hour))]
        .sort((a, b) => a - b);

    if (nightHours.length > 0) {
        return nightHours.map(h => `${hourLabel(h)} – ${hourLabel(h + 1)}`);
    }
    if (b.slot_type === 'daytime')  return ['6:00 AM – 6:00 PM'];
    if (b.slot_type === 'full_day') return ['Full Day'];
    return [b.slot_type ?? '—'];
}
</script>

<template>
    <AdminLayout>
        <div class="flex items-center justify-between mb-6">
            <h1 class="font-display font-bold text-2xl text-pitch-900">Bookings</h1>
        </div>

        <div class="card p-4 mb-5 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <input v-model="search" type="text" placeholder="Search name, mobile, reference" class="field-input col-span-2 sm:col-span-3 lg:col-span-2" />

            <select v-model="status" @change="applyFilters" class="field-input">
                <option value="">All statuses</option>
                <option value="pending">Pending</option>
                <option value="confirmed">Confirmed</option>
                <option value="cancelled">Cancelled</option>
                <option value="rejected">Rejected</option>
            </select>

            <select v-model="resourceId" @change="applyFilters" class="field-input">
                <option value="">All facilities</option>
                <option v-for="r in resources" :key="r.id" :value="r.id">{{ r.name }}</option>
            </select>

            <input v-model="from" @change="applyFilters" type="date" class="field-input" />
            <input v-model="to" @change="applyFilters" type="date" class="field-input" />

            <button
                v-if="hasFilters"
                @click="resetFilters"
                class="col-span-2 sm:col-span-3 lg:col-span-6 text-xs text-pitch-600 hover:text-pitch-900 font-medium text-left"
            >
                &times; Clear filters
            </button>
        </div>

        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-chalk-100 text-left text-xs uppercase tracking-wide text-ink-700/60">
                        <tr>
                            <th class="px-4 py-3">Reference</th>
                            <th class="px-4 py-3">Facility</th>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Date &amp; Slot</th>
                            <th class="px-4 py-3">Amount</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-chalk-200">
                        <tr v-for="b in bookings.data" :key="b.id" class="hover:bg-chalk-50">
                            <td class="px-4 py-3">
                                <Link :href="`/admin/bookings/${b.id}`" class="font-mono font-medium text-pitch-600 hover:underline">
                                    {{ b.reference_no }}
                                </Link>
                            </td>
                            <td class="px-4 py-3">{{ b.resource.name }}</td>
                            <td class="px-4 py-3">
                                <p class="font-medium">{{ b.full_name }}</p>
                                <p class="text-xs text-ink-700/50">{{ b.mobile_number }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span v-for="d in uniqueDates(b.dates)" :key="d.date" class="block text-xs font-medium text-ink-900 mb-1">
                                    {{ formatDate(d.date) }}
                                </span>
                                <div class="flex flex-wrap gap-1 mt-0.5">
                                    <span
                                        v-for="label in slotLabels(b)"
                                        :key="label"
                                        class="inline-block font-mono text-[10px] px-1.5 py-0.5 rounded bg-pitch-50 text-pitch-700 border border-pitch-200 whitespace-nowrap"
                                    >
                                        {{ label }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3">Rs. {{ Number(b.total_amount).toLocaleString() }}</td>
                            <td class="px-4 py-3"><StatusBadge :status="b.status" /></td>
                        </tr>
                        <tr v-if="bookings.data.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-ink-700/50">No bookings match these filters.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="bookings.links.length > 3" class="flex flex-wrap gap-1 px-4 py-3 border-t border-chalk-200">
                <Link
                    v-for="(link, i) in bookings.links"
                    :key="i"
                    :href="link.url ?? ''"
                    v-html="link.label"
                    class="px-3 py-1.5 rounded-md text-xs"
                    :class="[
                        link.active ? 'bg-pitch-900 text-chalk-50' : 'text-ink-700 hover:bg-chalk-100',
                        !link.url ? 'opacity-40 pointer-events-none' : '',
                    ]"
                />
            </div>
        </div>
    </AdminLayout>
</template>
