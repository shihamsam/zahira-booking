<script setup>
import { useForm, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
    booking: { type: Object, default: null },
    error:   { type: String, default: null },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);

const SLOT_LABELS = {
    full_day:     'Full Day',
    daytime:      'Daytime (8:30 AM – 6:30 PM)',
    night_4lights:'Night — 4 Lights',
    night_2lights:'Night — 2 Lights',
};

// ── Step 1: reference lookup ──────────────────────────────────────────────────
const refInput = ref('');

function findBooking() {
    const ref = refInput.value.trim().toUpperCase();
    if (ref) router.visit(`/upload-receipt/${ref}`);
}

// ── Step 2: file upload ───────────────────────────────────────────────────────
const uploadForm = useForm({ receipt: null });

function onFileChange(e) {
    uploadForm.receipt = e.target.files[0] ?? null;
}

function submitUpload() {
    uploadForm.post(`/upload-receipt/${props.booking.reference_no}`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => { uploadForm.receipt = null; },
    });
}

const isTerminal = computed(() =>
    props.booking && ['cancelled', 'rejected'].includes(props.booking.status)
);

function formatDate(d) {
    const [year, month, day] = d.slice(0, 10).split('-').map(Number);
    return new Date(year, month - 1, day).toLocaleDateString('en-GB', {
        day: 'numeric', month: 'short', year: 'numeric',
    });
}
</script>

<template>
    <PublicLayout>
        <div class="max-w-xl mx-auto px-4 sm:px-6 py-10 sm:py-14">

            <!-- ── Step 1: lookup ─────────────────────────────────────────── -->
            <template v-if="!booking">
                <div class="card p-6 sm:p-8">
                    <p class="font-mono text-xs uppercase tracking-widest text-pitch-600 mb-2">Payment receipt</p>
                    <h1 class="font-display font-bold text-2xl sm:text-3xl text-pitch-900 mb-2">Upload your receipt</h1>
                    <p class="text-sm text-ink-700/70 mb-8">
                        Enter your booking reference number to find your booking, then upload your bank deposit receipt.
                    </p>

                    <div v-if="error" class="mb-5 rounded-md bg-clay-500/10 border border-clay-500/30 text-clay-600 px-4 py-3 text-sm">
                        {{ error }}
                    </div>

                    <form @submit.prevent="findBooking" class="space-y-4">
                        <div>
                            <label class="field-label">Booking reference number</label>
                            <input
                                v-model="refInput"
                                type="text"
                                class="field-input font-mono tracking-widest uppercase"
                                placeholder="e.g. ZGR-20260716-AB1C"
                                autocomplete="off"
                                autofocus
                            />
                        </div>
                        <button
                            type="submit"
                            class="btn-primary w-full"
                            :disabled="!refInput.trim()"
                        >
                            Find booking
                        </button>
                    </form>
                </div>
            </template>

            <!-- ── Step 2: booking found ──────────────────────────────────── -->
            <template v-else>
                <!-- Success banner -->
                <div
                    v-if="flashSuccess"
                    class="mb-5 rounded-md bg-pitch-50 border border-pitch-400/30 text-pitch-900 px-4 py-3 text-sm"
                >
                    {{ flashSuccess }}
                </div>

                <!-- Booking summary card -->
                <div class="card p-6 sm:p-8 mb-5">
                    <div class="flex items-center justify-between mb-1">
                        <p class="font-mono text-xs uppercase tracking-widest text-pitch-600">Booking reference</p>
                        <StatusBadge :status="booking.status" />
                    </div>
                    <h1 class="font-display font-bold text-2xl text-pitch-900 mb-5">{{ booking.reference_no }}</h1>

                    <dl class="grid grid-cols-2 gap-y-2.5 text-sm">
                        <dt class="text-ink-700/60">Facility</dt>
                        <dd class="text-right font-medium">{{ booking.resource.name }}</dd>

                        <dt class="text-ink-700/60">Name</dt>
                        <dd class="text-right font-medium">{{ booking.full_name }}</dd>

                        <template v-if="booking.slot_type">
                            <dt class="text-ink-700/60">Slot</dt>
                            <dd class="text-right font-medium">{{ SLOT_LABELS[booking.slot_type] ?? booking.slot_type }}</dd>
                        </template>

                        <dt class="text-ink-700/60">Date(s)</dt>
                        <dd class="text-right font-medium">
                            <span v-for="d in booking.dates" :key="d.id" class="block">{{ formatDate(d.date) }}</span>
                        </dd>

                        <dt class="text-ink-700/60">Amount due</dt>
                        <dd class="text-right font-display font-semibold text-pitch-900">
                            Rs. {{ Number(booking.total_amount).toLocaleString() }}
                        </dd>
                    </dl>
                </div>

                <!-- Terminal status — no upload allowed -->
                <div v-if="isTerminal" class="card p-6 text-center">
                    <p class="text-sm text-ink-700/70">
                        This booking has been <strong>{{ booking.status }}</strong> and cannot accept a new receipt.
                        Please contact us if you believe this is an error.
                    </p>
                </div>

                <!-- Upload form -->
                <div v-else class="card p-6 sm:p-8">
                    <h2 class="font-display font-semibold uppercase tracking-wide text-sm text-pitch-900 mb-4">
                        {{ booking.receipt_path ? 'Replace receipt' : 'Upload receipt' }}
                    </h2>

                    <!-- Existing receipt preview -->
                    <div v-if="booking.receipt_path" class="mb-5">
                        <p class="text-xs text-ink-700/50 mb-2">Currently uploaded:</p>
                        <a :href="`/storage/${booking.receipt_path}`" target="_blank" class="block">
                            <img
                                v-if="!booking.receipt_path.endsWith('.pdf')"
                                :src="`/storage/${booking.receipt_path}`"
                                alt="Current receipt"
                                class="rounded-md border border-chalk-200 max-h-40 object-contain"
                            />
                            <span v-else class="inline-flex items-center gap-2 text-sm text-pitch-600 underline">
                                View current PDF receipt
                            </span>
                        </a>
                    </div>

                    <form @submit.prevent="submitUpload" class="space-y-4">
                        <div>
                            <label class="field-label">
                                Payment receipt <span class="text-clay-600">*</span>
                            </label>
                            <p class="text-xs text-ink-700/50 mb-2">JPG, PNG or PDF — max 5 MB.</p>
                            <input
                                type="file"
                                accept=".jpg,.jpeg,.png,.pdf"
                                @change="onFileChange"
                                class="block w-full text-sm text-ink-700 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:bg-chalk-100 file:text-ink-700 file:text-xs hover:file:bg-chalk-200"
                            />
                            <p v-if="uploadForm.errors.receipt" class="text-clay-600 text-xs mt-1">
                                {{ uploadForm.errors.receipt }}
                            </p>
                        </div>

                        <button
                            type="submit"
                            class="btn-primary w-full"
                            :disabled="uploadForm.processing || !uploadForm.receipt"
                        >
                            {{ uploadForm.processing ? 'Uploading...' : 'Submit receipt' }}
                        </button>
                    </form>

                    <p class="text-xs text-ink-700/50 mt-4 text-center">
                        We'll email you once your receipt is verified and your booking is confirmed.
                    </p>
                </div>

                <!-- Search again -->
                <p class="text-center mt-5 text-xs text-ink-700/50">
                    Wrong booking?
                    <a href="/upload-receipt" class="text-pitch-600 hover:underline">Search again</a>
                </p>
            </template>

        </div>
    </PublicLayout>
</template>
