<script setup>
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const copied = ref(false);

function copyReference() {
    navigator.clipboard.writeText(props.booking.reference_no).then(() => {
        copied.value = true;
        setTimeout(() => { copied.value = false; }, 2000);
    });
}

const props = defineProps({
    booking:        { type: Object, required: true },
    bank:           { type: Object, required: true },
    whatsappNumber: { type: String, default: '' },
});

const SLOT_LABELS = {
    full_day:     'Full Day',
    daytime:      'Daytime (6:00 AM – 6:00 PM)',
    night_4lights:'Night — 4 Lights',
    night_2lights:'Night — 2 Lights',
};

function formatDate(d) {
    return new Date(d).toLocaleDateString('en-GB', {
        day: 'numeric', month: 'short', year: 'numeric',
    });
}

function hourLabel(h) {
    const norm = ((h % 24) + 24) % 24;
    const ampm = norm < 12 ? 'AM' : 'PM';
    const disp = norm % 12 === 0 ? 12 : norm % 12;
    return `${disp}:00 ${ampm}`;
}

const uniqueDates = computed(() =>
    [...new Map(props.booking.dates.map(d => [d.date, d])).values()]
);

const nightSlotLabels = computed(() =>
    [...new Set(props.booking.dates.filter(d => d.slot_hour !== null).map(d => d.slot_hour))]
        .sort((a, b) => a - b)
        .map(h => `${hourLabel(h)} – ${hourLabel(h + 1)}`)
);

function buildWhatsAppMessage() {
    const ref   = props.booking.reference_no;
    const name  = props.booking.full_name;
    const phone = props.booking.mobile_number;
    const dates = uniqueDates.value.map(d => d.date.slice(0, 10)).join(', ');

    const timeLine = nightSlotLabels.value.length > 0
        ? nightSlotLabels.value.map(l => `- ${l}`).join('\n')
        : SLOT_LABELS[props.booking.slot_type] ?? props.booking.slot_type;

    const amount = `Rs. ${Number(props.booking.total_amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')}`;

    return [
        `*New Booking Request*`,
        `Ref: *${ref}*`,
        `Name: ${name}`,
        `Phone: ${phone}`,
        `Date: ${dates}`,
        `Times: ${timeLine}`,
        `Total Amount: ${amount}`,
        `Status: *Pending*`,
    ].join('\n');
}

function openWhatsApp() {
    const number  = props.whatsappNumber.replace(/\D/g, '');
    // encodeURIComponent converts * to %2A; WhatsApp web displays %2A literally
    // instead of applying bold formatting, so we decode it back.
    const encoded = encodeURIComponent(buildWhatsAppMessage()).replace(/%2A/g, '*');
    window.open(`https://wa.me/${number}?text=${encoded}`, '_blank');
}
</script>

<template>
    <PublicLayout>
        <div class="flex flex-col lg:h-[calc(100vh-9rem)] lg:overflow-hidden">

            <!-- ── Slim title bar ──────────────────────────────────────────────── -->
            <div class="flex-shrink-0 bg-white border-b border-chalk-200 px-4 sm:px-6 py-2.5">
                <div class="max-w-7xl mx-auto flex items-center gap-4">
                    <div class="flex-1 min-w-0">
                        <p class="font-mono text-[10px] uppercase tracking-widest text-pitch-600 leading-none mb-0.5">
                            Booking Confirmation
                        </p>
                        <h1 class="font-display font-bold text-base sm:text-lg text-pitch-900 leading-tight">
                            {{ booking.resource.name }}
                        </h1>
                    </div>
                    <StatusBadge :status="booking.status" class="flex-shrink-0" />
                    <div class="w-px h-7 bg-chalk-200 flex-shrink-0 hidden sm:block"></div>
                    <Link href="/" class="flex-shrink-0 text-xs text-ink-700/55 hover:text-pitch-900 transition-colors font-medium hidden sm:block">
                        ← Home
                    </Link>
                </div>
            </div>

            <!-- ── Two-panel content ───────────────────────────────────────────── -->
            <div class="flex-1 min-h-0 overflow-hidden">
            <div class="max-w-7xl mx-auto h-full flex flex-col lg:flex-row divide-y lg:divide-y-0 lg:divide-x divide-chalk-200 overflow-hidden">

                <!-- LEFT — Booking summary -->
                <div class="lg:w-96 xl:w-[420px] flex-shrink-0 overflow-y-auto px-4 sm:px-6 py-5">

                    <p class="font-mono text-[10px] uppercase tracking-widest text-pitch-600 mb-3">Booking details</p>

                    <dl class="grid grid-cols-2 gap-y-2.5 text-sm">
                        <dt class="text-ink-700/60">Facility</dt>
                        <dd class="text-right font-medium">{{ booking.resource.name }}</dd>

                        <dt class="text-ink-700/60">Name</dt>
                        <dd class="text-right font-medium">{{ booking.full_name }}</dd>

                        <dt class="text-ink-700/60">Phone</dt>
                        <dd class="text-right font-medium">{{ booking.mobile_number }}</dd>

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

                        <template v-if="booking.chair_count">
                            <dt class="text-ink-700/60">Chairs</dt>
                            <dd class="text-right font-medium">{{ booking.chair_count }}</dd>
                        </template>

                        <template v-if="booking.sound_system_requested">
                            <dt class="text-ink-700/60">Sound system</dt>
                            <dd class="text-right font-medium">Requested</dd>
                        </template>

                        <dt class="text-ink-700/60 font-semibold pt-2 border-t border-chalk-200">Amount due</dt>
                        <dd class="text-right font-display font-bold text-pitch-900 text-base pt-2 border-t border-chalk-200">
                            Rs. {{ Number(booking.total_amount).toLocaleString() }}
                        </dd>
                    </dl>
                </div>

                <!-- RIGHT — Actions -->
                <div class="flex-1 min-w-0 overflow-y-auto px-4 sm:px-6 py-5 space-y-4">

                    <!-- Receipt -->
                    <div class="rounded-card p-4" :class="booking.receipt_path ? 'bg-pitch-50' : 'bg-floodlight-500/10 border border-floodlight-500/30'">
                        <template v-if="booking.receipt_path">
                            <p class="font-mono text-[10px] uppercase tracking-widest text-pitch-600 mb-2">Receipt received</p>
                            <p class="text-sm text-ink-700 mb-3">
                                Your receipt has been uploaded. Admin will verify it and confirm your booking shortly.
                            </p>
                            <a :href="`/storage/${booking.receipt_path}`" target="_blank" class="block">
                                <img
                                    v-if="!booking.receipt_path.endsWith('.pdf')"
                                    :src="`/storage/${booking.receipt_path}`"
                                    alt="Your uploaded receipt"
                                    class="rounded-md border border-pitch-900/10 max-h-32 object-contain"
                                />
                                <span v-else class="inline-flex items-center gap-2 text-sm text-pitch-600 underline">
                                    View uploaded PDF receipt
                                </span>
                            </a>
                        </template>

                        <template v-else>
                            <p class="font-mono text-[10px] uppercase tracking-widest text-floodlight-600 mb-3">Receipt not yet uploaded</p>

                            <!-- Reference line with copy button -->
                            <div class="flex items-center gap-2 mb-3">
                                <p class="font-mono font-bold text-lg text-pitch-900 tracking-widest leading-none">
                                    {{ booking.reference_no }}
                                </p>
                                <button
                                    type="button"
                                    @click="copyReference"
                                    :title="copied ? 'Copied!' : 'Copy reference number'"
                                    class="flex-shrink-0 p-1 rounded transition-colors"
                                    :class="copied ? 'text-pitch-600' : 'text-ink-700/30 hover:text-ink-700'"
                                >
                                    <svg v-if="!copied" xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                                    </svg>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </button>
                            </div>

                            <p class="text-sm text-ink-700 mb-2">
                                Your booking is reserved. Make the bank deposit and upload your receipt to complete the process.
                            </p>
                            <p class="text-sm text-ink-700 mb-4">
                                When making the transfer, enter the reference number above in the bank's
                                <strong class="font-semibold text-pitch-900">Transfer / Deposit Reference</strong>
                                field so we can match your payment.
                            </p>
                            <Link :href="`/upload-receipt/${booking.reference_no}`" class="btn-primary inline-block text-sm py-2">
                                Upload receipt now
                            </Link>
                        </template>
                    </div>

                    <!-- Bank details -->
                    <div>
                        <p class="font-mono text-[10px] uppercase tracking-widest text-pitch-600 mb-2">Bank transfer details</p>
                        <dl class="grid grid-cols-2 gap-y-2 text-sm bg-chalk-50 rounded-card px-4 py-3 border border-chalk-200">
                            <dt class="text-ink-700/60">Bank</dt>
                            <dd class="text-right font-medium">{{ bank.bank_name }}</dd>
                            <dt class="text-ink-700/60">Account name</dt>
                            <dd class="text-right font-medium">{{ bank.account_name }}</dd>
                            <dt class="text-ink-700/60">Account no.</dt>
                            <dd class="text-right font-mono font-medium">{{ bank.account_number }}</dd>
                            <dt class="text-ink-700/60">Branch</dt>
                            <dd class="text-right font-medium">{{ bank.branch }}</dd>
                        </dl>
                    </div>

                    <!-- WhatsApp -->
                    <div v-if="whatsappNumber" class="flex items-center gap-3">
                        <button
                            type="button"
                            @click="openWhatsApp"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-md bg-[#25D366] text-white text-sm font-semibold hover:bg-[#1ebe5c] transition-colors flex-shrink-0"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            Send via WhatsApp
                        </button>
                        <p class="text-xs text-ink-700/55 leading-snug">
                            Share your booking summary with us for a faster response.
                        </p>
                    </div>

                    <!-- Footer links -->
                    <div class="pt-3 border-t border-chalk-200 space-y-1.5">
                        <p class="text-xs text-ink-700/45 text-center">
                            Keep your reference number handy — you'll need it for any follow-up.
                        </p>
                        <Link
                            :href="`/upload-receipt/${booking.reference_no}`"
                            class="block text-center text-xs text-pitch-600 hover:underline"
                        >
                            Need to upload a different receipt?
                        </Link>
                    </div>

                </div>
            </div>
            </div>

        </div>
    </PublicLayout>
</template>
