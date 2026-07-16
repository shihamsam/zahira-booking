<script setup>
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    filters:    { type: Object, required: true },
    resources:  { type: Array,  default: () => [] },
    summary:    { type: Object, required: true },
    breakdown:  { type: Array,  default: () => [] },
    byResource: { type: Array,  default: () => [] },
});

const preset     = ref(props.filters.preset);
const from       = ref(props.filters.from);
const to         = ref(props.filters.to);
const resourceId = ref(props.filters.resource_id ?? '');

function apply(overrides = {}) {
    router.get('/admin/reports', {
        preset: preset.value,
        from: from.value,
        to: to.value,
        resource_id: resourceId.value || undefined,
        ...overrides,
    }, { preserveState: true, replace: true });
}

function setPreset(p) {
    preset.value = p;
    router.get('/admin/reports', {
        preset: p,
        resource_id: resourceId.value || undefined,
    }, { preserveState: true, replace: true, onSuccess: (page) => {
        from.value = page.props.filters.from;
        to.value   = page.props.filters.to;
    }});
}

function buildParams() {
    return new URLSearchParams({
        from: from.value,
        to: to.value,
        ...(resourceId.value ? { resource_id: resourceId.value } : {}),
    });
}

function exportCsv()   { window.location.href = `/admin/reports/export?${buildParams()}&format=csv`; }
function exportExcel() { window.location.href = `/admin/reports/export?${buildParams()}&format=excel`; }
</script>

<template>
    <AdminLayout>
        <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
            <h1 class="font-display font-bold text-2xl text-pitch-900">Income reports</h1>
            <div class="flex gap-2">
                <button @click="exportCsv"   class="btn-outline text-xs">Export CSV</button>
                <button @click="exportExcel" class="btn-outline text-xs">Export Excel</button>
            </div>
        </div>

        <!-- Filters -->
        <div class="card p-4 mb-6 flex flex-wrap items-end gap-3">
            <div class="flex gap-1.5 flex-wrap">
                <button
                    v-for="p in ['weekly', 'monthly', 'quarterly', 'yearly']"
                    :key="p"
                    @click="setPreset(p)"
                    class="px-3.5 py-2 rounded-md text-xs font-semibold uppercase tracking-wide"
                    :class="preset === p ? 'bg-pitch-900 text-chalk-50' : 'bg-chalk-100 text-ink-700 hover:bg-chalk-200'"
                >
                    {{ p }}
                </button>
            </div>

            <div class="flex items-end gap-2">
                <div>
                    <label class="field-label">From</label>
                    <input v-model="from" @change="apply()" type="date" class="field-input" />
                </div>
                <div>
                    <label class="field-label">To</label>
                    <input v-model="to" @change="apply()" type="date" class="field-input" />
                </div>
            </div>

            <div>
                <label class="field-label">Facility</label>
                <select v-model="resourceId" @change="apply()" class="field-input">
                    <option value="">All facilities</option>
                    <option v-for="r in resources" :key="r.id" :value="r.id">{{ r.name }}</option>
                </select>
            </div>
        </div>

        <!-- Summary -->
        <div class="grid sm:grid-cols-2 gap-4 mb-6">
            <div class="card p-5">
                <p class="text-xs uppercase tracking-wide text-ink-700/60 mb-1.5">Total income (confirmed)</p>
                <p class="font-display font-bold text-3xl text-pitch-900">Rs. {{ Number(summary.total_income).toLocaleString() }}</p>
            </div>
            <div class="card p-5">
                <p class="text-xs uppercase tracking-wide text-ink-700/60 mb-1.5">Confirmed bookings</p>
                <p class="font-display font-bold text-3xl text-pitch-900">{{ summary.total_bookings }}</p>
            </div>
        </div>

        <!-- Breakdown tables -->
        <div class="grid lg:grid-cols-2 gap-6">
            <div class="card overflow-hidden">
                <h2 class="font-display font-semibold uppercase tracking-wide text-sm text-pitch-900 px-5 py-4 border-b border-chalk-200">
                    By period
                </h2>
                <table class="w-full text-sm">
                    <thead class="bg-chalk-100 text-left text-xs uppercase tracking-wide text-ink-700/60">
                        <tr>
                            <th class="px-5 py-2.5">Period</th>
                            <th class="px-5 py-2.5">Bookings</th>
                            <th class="px-5 py-2.5">Income</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-chalk-200">
                        <tr v-for="row in breakdown" :key="row.period">
                            <td class="px-5 py-2.5 font-mono">{{ row.period }}</td>
                            <td class="px-5 py-2.5">{{ row.bookings }}</td>
                            <td class="px-5 py-2.5">Rs. {{ Number(row.income).toLocaleString() }}</td>
                        </tr>
                        <tr v-if="breakdown.length === 0">
                            <td colspan="3" class="px-5 py-6 text-center text-ink-700/50">No confirmed income in this range.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="card overflow-hidden">
                <h2 class="font-display font-semibold uppercase tracking-wide text-sm text-pitch-900 px-5 py-4 border-b border-chalk-200">
                    By facility
                </h2>
                <table class="w-full text-sm">
                    <thead class="bg-chalk-100 text-left text-xs uppercase tracking-wide text-ink-700/60">
                        <tr>
                            <th class="px-5 py-2.5">Facility</th>
                            <th class="px-5 py-2.5">Bookings</th>
                            <th class="px-5 py-2.5">Income</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-chalk-200">
                        <tr v-for="row in byResource" :key="row.resource_name">
                            <td class="px-5 py-2.5">{{ row.resource_name }}</td>
                            <td class="px-5 py-2.5">{{ row.bookings }}</td>
                            <td class="px-5 py-2.5">Rs. {{ Number(row.income).toLocaleString() }}</td>
                        </tr>
                        <tr v-if="byResource.length === 0">
                            <td colspan="3" class="px-5 py-6 text-center text-ink-700/50">No confirmed income in this range.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
