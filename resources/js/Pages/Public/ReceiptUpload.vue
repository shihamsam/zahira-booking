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
    daytime:      'Daytime (6:00 AM – 6:00 PM)',
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

// ── Night slot helpers (same as BookingConfirmation) ──────────────────────────
function hourLabel(h) {
    const norm = ((h % 24) + 24) % 24;
    const ampm = norm < 12 ? 'AM' : 'PM';
    const disp = norm % 12 === 0 ? 12 : norm % 12;
    return `${disp}:00 ${ampm}`;
}

const uniqueDates = computed(() =>
    props.booking
        ? [...new Map(props.booking.dates.map(d => [d.date, d])).values()]
        : []
);

const nightSlotLabels = computed(() =>
    props.booking
        ? [...new Set(props.booking.dates.filter(d => d.slot_hour !== null).map(d => d.slot_hour))]
            .sort((a, b) => a - b)
            .map(h => `${hourLabel(h)} – ${hourLabel(h + 1)}`)
        : []
);
</script>

<template>
    <PublicLayout>
        <div class="flex flex-col lg:h-[calc(100vh-9rem)] lg:overflow-hidden">

            <!-- ═══════════════════════════════════════════════════════════
                 STATE 1 — Reference lookup (no booking loaded yet)
            ═══════════════════════════════════════════════════════════ -->
            <template v-if="!booking">
                <div class="flex-1 flex items-center justify-center px-4 sm:px-6 py-10">
                    <div class="w-full max-w-md card p-6 sm:p-8">
                        <p class="font-mono text-[10px] uppercase tracking-widest text-pitch-600 mb-1">Payment receipt</p>
                        <h1 class="font-display font-bold text-2xl text-pitch-900 mb-2">Upload your receipt</h1>
                        <p class="text-sm text-ink-700/70 mb-6">
                            Enter your booking reference to find your booking, then upload your bank deposit receipt.
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
                            <button type="submit" class="btn-primary w-full" :disabled="!refInput.trim()">
                                Find booking
                            </button>
                        </form>
                    </div>
                </div>
            </template>

            <!-- ═══════════════════════════════════════════════════════════
                 STATE 2 — Booking found: slim title bar + two panels
            ═══════════════════════════════════════════════════════════ -->
            <template v-else>

                <!-- Slim title bar -->
                <div class="flex-shrink-0 bg-white border-b border-chalk-200 px-4 sm:px-6 py-2.5">
                    <div class="max-w-7xl mx-auto flex items-center gap-4">
                        <div class="flex-1 min-w-0">
                            <h1 class="font-display font-bold text-base sm:text-lg text-pitch-900 leading-tight">
                                Upload Receipt
                            </h1>
                        </div>
                        <StatusBadge :status="booking.status" class="flex-shrink-0" />
                        <div class="w-px h-7 bg-chalk-200 flex-shrink-0 hidden sm:block"></div>
                        <a href="/upload-receipt" class="flex-shrink-0 text-xs text-ink-700/55 hover:text-pitch-900 transition-colors font-medium hidden sm:block">
                            ← Search again
                        </a>
                    </div>
                </div>

                <!-- Two-panel content -->
                <div class="flex-1 min-h-0 overflow-hidden">
                <div class="max-w-7xl mx-auto h-full flex flex-col lg:flex-row divide-y lg:divide-y-0 lg:divide-x divide-chalk-200 overflow-hidden">

                    <!-- LEFT — Booking summary -->
                    <div class="lg:w-96 xl:w-[420px] flex-shrink-0 overflow-y-auto px-4 sm:px-6 py-5">

                        <!-- Success banner -->
                        <div
                            v-if="flashSuccess"
                            class="mb-4 rounded-md bg-pitch-50 border text-pitch-900 px-3 py-2.5 text-sm font-medium neon-glow-border"
                        >
                            {{ flashSuccess }}
                        </div>

                        <p class="font-mono text-[10px] uppercase tracking-widest text-pitch-600 mb-3">Booking details</p>

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
                                <span v-for="d in uniqueDates" :key="d.date" class="block">{{ formatDate(d.date) }}</span>
                            </dd>

                            <template v-if="nightSlotLabels.length > 0">
                                <dt class="text-ink-700/60">Night slot(s)</dt>
                                <dd class="text-right font-medium">
                                    <span v-for="label in nightSlotLabels" :key="label" class="block font-mono text-xs">{{ label }}</span>
                                </dd>
                            </template>

                            <dt class="text-ink-700/60 font-semibold pt-2 border-t border-chalk-200">Amount due</dt>
                            <dd class="text-right font-display font-bold text-pitch-900 text-base pt-2 border-t border-chalk-200">
                                Rs. {{ Number(booking.total_amount).toLocaleString() }}
                            </dd>
                        </dl>
                    </div>

                    <!-- RIGHT — Upload form or terminal message -->
                    <div class="flex-1 min-w-0 overflow-y-auto px-4 sm:px-6 py-5 flex flex-col items-start justify-start">

                        <!-- Terminal status -->
                        <div v-if="isTerminal" class="flex-1 flex items-center justify-center w-full">
                            <p class="text-sm text-ink-700/70 text-center max-w-xs">
                                This booking has been <strong>{{ booking.status }}</strong> and cannot accept a new receipt.
                                Please contact us if you believe this is an error.
                            </p>
                        </div>

                        <!-- Upload form — contained card, not full-width -->
                        <div v-else class="w-full max-w-sm">
                            <p class="font-mono text-[10px] uppercase tracking-widest text-pitch-600 mb-3">
                                {{ booking.receipt_path ? 'Replace receipt' : 'Upload receipt' }}
                            </p>

                            <div class="card p-5 space-y-4">
                                <!-- Reference header -->
                                <div class="bg-pitch-900 rounded-md px-4 py-3">
                                    <p class="font-mono text-[10px] uppercase tracking-widest text-chalk-50/50 mb-0.5">Booking Reference</p>
                                    <p class="font-mono font-bold text-base text-chalk-50 tracking-widest">{{ booking.reference_no }}</p>
                                </div>

                                <!-- Existing receipt preview -->
                                <div v-if="booking.receipt_path">
                                    <p class="text-xs text-ink-700/50 mb-2">Currently uploaded:</p>
                                    <a :href="`/storage/${booking.receipt_path}`" target="_blank" class="block">
                                        <img
                                            v-if="!booking.receipt_path.endsWith('.pdf')"
                                            :src="`/storage/${booking.receipt_path}`"
                                            alt="Current receipt"
                                            class="rounded-md border border-chalk-200 max-h-36 object-contain"
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

                                <p class="text-xs text-ink-700/50 text-center">
                                    We'll notify you once your receipt is verified and your booking is confirmed.
                                </p>
                            </div>
                        </div>

                    </div>
                </div>
                </div>

            </template>

        </div>
    </PublicLayout>
</template>
