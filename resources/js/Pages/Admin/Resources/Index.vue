<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    resources: { type: Array, required: true },
});

// Track which resource is being edited
const editingId = ref(null);

// One form per resource, keyed by resource id
function makeForm(resource) {
    return useForm({
        pricing: (resource.resolved_pricing ?? []).map(p => ({
            slot_type: p.slot_type,
            label:     p.label,
            type:      p.type,
            rate:      p.rate,
        })),
        is_active: resource.is_active,
    });
}

const forms = Object.fromEntries(
    props.resources.map(r => [r.id, makeForm(r)])
);

function save(resource) {
    forms[resource.id].put(`/admin/resources/${resource.id}`, {
        preserveScroll: true,
        onSuccess: () => { editingId.value = null; },
    });
}
</script>

<template>
    <AdminLayout>
        <h1 class="font-display font-bold text-2xl text-pitch-900 mb-6">Facilities &amp; Pricing</h1>

        <div class="space-y-5">
            <div v-for="resource in resources" :key="resource.id" class="card p-5">
                <div class="flex items-start justify-between mb-4 flex-wrap gap-2">
                    <div>
                        <h2 class="font-display font-semibold text-lg text-pitch-900">{{ resource.name }}</h2>
                        <p class="text-xs text-ink-700/50 mt-0.5">{{ resource.location }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span
                            class="text-xs font-medium px-2.5 py-1 rounded-full border"
                            :class="resource.is_active
                                ? 'bg-pitch-50 text-pitch-600 border-pitch-600/30'
                                : 'bg-chalk-100 text-ink-700/50 border-chalk-200'"
                        >
                            {{ resource.is_active ? 'Active' : 'Inactive' }}
                        </span>
                        <button
                            @click="editingId = editingId === resource.id ? null : resource.id"
                            class="btn-outline text-xs"
                        >
                            {{ editingId === resource.id ? 'Cancel' : 'Edit pricing' }}
                        </button>
                    </div>
                </div>

                <!-- Read-only pricing table -->
                <div v-if="editingId !== resource.id">
                    <table v-if="resource.resolved_pricing?.length" class="w-full text-sm">
                        <thead class="text-left text-xs uppercase tracking-wide text-ink-700/50">
                            <tr>
                                <th class="pb-2">Slot</th>
                                <th class="pb-2">Type</th>
                                <th class="pb-2 text-right">Rate (LKR)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-chalk-100">
                            <tr v-for="p in resource.resolved_pricing" :key="p.slot_type">
                                <td class="py-2 font-medium">{{ p.label }}</td>
                                <td class="py-2 text-ink-700/60 capitalize">{{ p.type }}</td>
                                <td class="py-2 text-right font-mono">
                                    Rs. {{ Number(p.rate).toLocaleString() }}
                                    <span class="text-ink-700/40 text-xs">{{ p.type === 'hourly' ? '/hr' : '/day' }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-else class="text-sm text-ink-700/50">No slot pricing configured for this facility.</p>
                </div>

                <!-- Edit form -->
                <form v-else @submit.prevent="save(resource)" class="space-y-4">
                    <div class="flex items-center gap-3 pb-3 border-b border-chalk-200">
                        <input
                            :id="`active-${resource.id}`"
                            v-model="forms[resource.id].is_active"
                            type="checkbox"
                            class="h-4 w-4 rounded border-chalk-300 text-pitch-900"
                        />
                        <label :for="`active-${resource.id}`" class="text-sm text-ink-700 cursor-pointer">
                            Facility is active (visible to public)
                        </label>
                    </div>

                    <div v-for="(slot, idx) in forms[resource.id].pricing" :key="slot.slot_type" class="flex items-center gap-4">
                        <label class="text-sm text-ink-700 w-56 shrink-0">
                            {{ slot.label }}
                            <span class="text-xs text-ink-700/40 block">{{ slot.type === 'hourly' ? 'per hour' : 'per day' }}</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-ink-700/60">Rs.</span>
                            <input
                                v-model.number="forms[resource.id].pricing[idx].rate"
                                type="number"
                                min="0"
                                step="100"
                                class="field-input w-32"
                            />
                        </div>
                    </div>

                    <p v-if="forms[resource.id].errors.pricing" class="text-clay-600 text-xs">
                        {{ forms[resource.id].errors.pricing }}
                    </p>

                    <div class="flex gap-3 pt-2">
                        <button type="submit" class="btn-primary" :disabled="forms[resource.id].processing">
                            {{ forms[resource.id].processing ? 'Saving...' : 'Save changes' }}
                        </button>
                        <button type="button" class="btn-outline" @click="editingId = null">Cancel</button>
                    </div>
                </form>
            </div>

            <p v-if="resources.length === 0" class="text-sm text-ink-700/50">No facilities found.</p>
        </div>
    </AdminLayout>
</template>
