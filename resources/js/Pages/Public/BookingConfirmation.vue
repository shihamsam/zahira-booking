<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
    booking: { type: Object, required: true },
    bank:    { type: Object, required: true },
});

const SLOT_LABELS = {
    full_day:     'Full Day',
    daytime:      'Daytime (8:30 AM – 6:30 PM)',
    night_4lights:'Night — 4 Lights',
    night_2lights:'Night — 2 Lights',
};

function formatDate(d) {
    return new Date(d).toLocaleDateString('en-GB', {
        day: 'numeric', month: 'short', year: 'numeric',
    });
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

                    <template v-if="booking.hours">
                        <dt class="text-ink-700/60">Duration</dt>
                        <dd class="text-right font-medium">{{ booking.hours }} hour(s)</dd>
                    </template>

                    <dt class="text-ink-700/60">Date(s)</dt>
                    <dd class="text-right font-medium">
                        <span v-for="d in booking.dates" :key="d.id" class="block">{{ formatDate(d.date) }}</span>
                    </dd>

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

                <!-- Receipt confirmation -->
                <div class="bg-pitch-50 rounded-card p-5 mb-6">
                    <h2 class="font-display font-semibold uppercase tracking-wide text-sm text-pitch-900 mb-3">
                        Receipt received
                    </h2>
                    <p class="text-sm text-ink-700 mb-3">
                        Your payment receipt has been uploaded. Our admin will verify it and confirm your booking.
                        You will be notified once the booking is approved.
                    </p>

                    <a
                        v-if="booking.receipt_path"
                        :href="`/storage/${booking.receipt_path}`"
                        target="_blank"
                        class="block"
                    >
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

                <p class="text-xs text-ink-700/50 text-center">
                    Save your reference number &mdash; you'll need it if you contact us about this booking.
                </p>
            </div>
        </div>
    </PublicLayout>
</template>
