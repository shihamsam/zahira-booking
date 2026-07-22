<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

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
    const dates = uniqueDates.value.map(d => d.date).join(', ');

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
    const message = encodeURIComponent(buildWhatsAppMessage());
    window.open(`https://wa.me/${number}?text=${message}`, '_blank');
}
</script>

<template>
    <PublicLayout>
        <div class="max-w-2xl mx-auto px-4 sm:px-6 py-10 sm:py-14">
            <div class="card p-6 sm:p-8">
                <div class="flex items-center justify-between mb-1">
                    <p class="font-mono text-xs uppercase tracking-widest text-pitch-600">Booking reference</p>
                    <StatusBadge :status="booking.status" />
                </div>
                <h1 class="font-display font-bold text-2xl sm:text-3xl text-pitch-900 mb-6">
                    {{ booking.reference_no }}
                </h1>

                <dl class="grid grid-cols-2 gap-y-3 text-sm mb-6">
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

                    <template v-if="booking.chair_count">
                        <dt class="text-ink-700/60">Chairs</dt>
                        <dd class="text-right font-medium">{{ booking.chair_count }}</dd>
                    </template>

                    <template v-if="booking.sound_system_requested">
                        <dt class="text-ink-700/60">Sound system</dt>
                        <dd class="text-right font-medium">Requested</dd>
                    </template>

                    <dt class="text-ink-700/60">Amount due</dt>
                    <dd class="text-right font-display font-semibold text-pitch-900">
                        Rs. {{ Number(booking.total_amount).toLocaleString() }}
                    </dd>
                </dl>

                <!-- Receipt section — adapts based on whether one was uploaded -->
                <div class="rounded-card p-5 mb-6" :class="booking.receipt_path ? 'bg-pitch-50' : 'bg-floodlight-500/10 border border-floodlight-500/30'">
                    <template v-if="booking.receipt_path">
                        <h2 class="font-display font-semibold uppercase tracking-wide text-sm text-pitch-900 mb-3">
                            Receipt received
                        </h2>
                        <p class="text-sm text-ink-700 mb-3">
                            Your payment receipt has been uploaded. Our admin will verify it and confirm your booking shortly.
                        </p>
                        <a :href="`/storage/${booking.receipt_path}`" target="_blank" class="block">
                            <img
                                v-if="!booking.receipt_path.endsWith('.pdf')"
                                :src="`/storage/${booking.receipt_path}`"
                                alt="Your uploaded receipt"
                                class="rounded-md border border-pitch-900/10 max-h-40 object-contain"
                            />
                            <span v-else class="inline-flex items-center gap-2 text-sm text-pitch-600 underline">
                                View uploaded PDF receipt
                            </span>
                        </a>
                    </template>

                    <template v-else>
                        <h2 class="font-display font-semibold uppercase tracking-wide text-sm text-floodlight-600 mb-2">
                            Receipt not yet uploaded
                        </h2>
                        <p class="text-sm text-ink-700 mb-4">
                            Your booking is reserved but payment has not been verified yet.
                            Please make the bank deposit and upload your receipt to complete the process.
                        </p>
                        <Link
                            :href="`/upload-receipt/${booking.reference_no}`"
                            class="btn-primary inline-block"
                        >
                            Upload receipt now
                        </Link>
                    </template>
                </div>

                <!-- Bank details for reference -->
                <div class="border border-chalk-200 rounded-card p-5 mb-6">
                    <h2 class="font-display font-semibold uppercase tracking-wide text-sm text-pitch-900 mb-3">
                        Bank account (for reference)
                    </h2>
                    <dl class="grid grid-cols-2 gap-y-2 text-sm">
                        <dt class="text-ink-700/60">Bank</dt>
                        <dd class="text-right font-medium">{{ bank.bank_name }}</dd>
                        <dt class="text-ink-700/60">Account name</dt>
                        <dd class="text-right font-medium">{{ bank.account_name }}</dd>
                        <dt class="text-ink-700/60">Account number</dt>
                        <dd class="text-right font-mono font-medium">{{ bank.account_number }}</dd>
                        <dt class="text-ink-700/60">Branch</dt>
                        <dd class="text-right font-medium">{{ bank.branch }}</dd>
                    </dl>
                </div>

                <!-- WhatsApp summary -->
                <div v-if="whatsappNumber" class="border border-chalk-200 rounded-card p-5 mb-6 bg-[#f0fdf4]">
                    <h2 class="font-display font-semibold uppercase tracking-wide text-sm text-pitch-900 mb-2">
                        Share via WhatsApp
                    </h2>
                    <p class="text-xs text-ink-700/70 mb-3">
                        Send your booking summary to us on WhatsApp to get a faster response.
                    </p>
                    <button
                        type="button"
                        @click="openWhatsApp"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-md bg-[#25D366] text-white text-sm font-semibold hover:bg-[#1ebe5c] transition-colors"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        Send via WhatsApp
                    </button>
                </div>

                <p class="text-xs text-ink-700/50 text-center mb-3">
                    Save your reference number &mdash; you'll need it if you contact us about this booking.
                </p>
                <Link
                    :href="`/upload-receipt/${booking.reference_no}`"
                    class="block text-center text-xs text-pitch-600 hover:underline"
                >
                    Need to upload a different receipt?
                </Link>
            </div>
        </div>
    </PublicLayout>
</template>
