<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { computed } from 'vue';

const props = defineProps({
    booking: { type: Object, required: true },
    bank: { type: Object, required: true },
    whatsappNumber: { type: String, required: true },
});

function formatDate(d) {
    return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

const whatsappLink = computed(() => {
    const msg = encodeURIComponent(
        `Hi, I'd like to send my deposit receipt for booking ${props.booking.reference_no} (${props.booking.resource.name}).`
    );
    return `https://wa.me/${props.whatsappNumber}?text=${msg}`;
});
</script>

<template>
    <PublicLayout>
        <div class="max-w-2xl mx-auto px-4 sm:px-6 py-10 sm:py-14">
            <div class="card p-6 sm:p-8">
                <div class="flex items-center justify-between mb-1">
                    <p class="font-mono text-xs uppercase tracking-widest text-pitch-600">Booking reference</p>
                    <StatusBadge :status="booking.status" />
                </div>
                <h1 class="font-display font-bold text-2xl sm:text-3xl text-pitch-900 mb-6">{{ booking.reference_no }}</h1>

                <dl class="grid grid-cols-2 gap-y-3 text-sm mb-6">
                    <dt class="text-ink-700/60">Ground</dt>
                    <dd class="text-right font-medium">{{ booking.resource.name }}</dd>
                    <dt class="text-ink-700/60">Name</dt>
                    <dd class="text-right font-medium">{{ booking.full_name }}</dd>
                    <dt class="text-ink-700/60">Dates</dt>
                    <dd class="text-right font-medium">
                        <span v-for="d in booking.dates" :key="d.id" class="block">{{ formatDate(d.date) }}</span>
                    </dd>
                    <dt class="text-ink-700/60">Amount due</dt>
                    <dd class="text-right font-display font-semibold text-pitch-900">Rs. {{ Number(booking.total_amount).toLocaleString() }}</dd>
                </dl>

                <div class="bg-pitch-50 rounded-card p-5 mb-6">
                    <h2 class="font-display font-semibold uppercase tracking-wide text-sm text-pitch-900 mb-3">
                        How to pay
                    </h2>
                    <ol class="space-y-2 text-sm text-ink-700 list-decimal list-inside">
                        <li>Deposit <strong>Rs. {{ Number(booking.total_amount).toLocaleString() }}</strong> to the bank account below.</li>
                        <li>Take a photo of the deposit slip / receipt.</li>
                        <li>Send it to our WhatsApp number along with your reference <strong>{{ booking.reference_no }}</strong>.</li>
                        <li>We'll confirm your booking once the receipt is verified.</li>
                    </ol>

                    <dl class="mt-4 grid grid-cols-2 gap-y-2 text-sm border-t border-pitch-900/10 pt-4">
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

                <a :href="whatsappLink" target="_blank" rel="noopener" class="btn-primary w-full">
                    Send receipt on WhatsApp
                </a>

                <p class="text-xs text-ink-700/50 mt-4 text-center">
                    Save your reference number &mdash; you'll need it if you contact us about this booking.
                </p>
            </div>
        </div>
    </PublicLayout>
</template>
