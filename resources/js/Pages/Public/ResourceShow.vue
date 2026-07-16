<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import Calendar from '@/Components/Calendar.vue';

const props = defineProps({
    resource: { type: Object, required: true },
    unavailableDates: { type: Array, default: () => [] },
    bookingWindow: { type: Object, required: true },
});

const form = useForm({
    full_name: '',
    mobile_number: '',
    purpose: '',
    dates: [],
});

const totalAmount = computed(() => form.dates.length * Number(props.resource.price_per_day));

function submit() {
    form.post(`/grounds/${props.resource.slug}/bookings`);
}

function formatDate(d) {
    return new Date(d + 'T00:00:00').toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

function removeDate(d) {
    form.dates = form.dates.filter((x) => x !== d);
}
</script>

<template>
    <PublicLayout>
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-8 sm:py-12">
            <p class="font-mono text-xs uppercase tracking-widest text-pitch-600 mb-2">Ground</p>
            <h1 class="font-display font-bold text-2xl sm:text-3xl text-pitch-900 mb-2">{{ resource.name }}</h1>
            <p class="text-ink-700/70 max-w-xl mb-8">{{ resource.description }}</p>

            <div class="grid lg:grid-cols-5 gap-6 lg:gap-8">
                <div class="lg:col-span-3">
                    <h2 class="font-display font-semibold uppercase tracking-wide text-sm text-pitch-900 mb-3">
                        1. Select your date(s)
                    </h2>
                    <Calendar
                        v-model="form.dates"
                        :unavailable-dates="unavailableDates"
                        :min-date="bookingWindow.from"
                        :max-date="bookingWindow.to"
                    />
                    <p v-if="form.errors.dates" class="text-clay-600 text-sm mt-2">{{ form.errors.dates }}</p>
                </div>

                <div class="lg:col-span-2">
                    <h2 class="font-display font-semibold uppercase tracking-wide text-sm text-pitch-900 mb-3">
                        2. Your details
                    </h2>

                    <form @submit.prevent="submit" class="card p-5 space-y-4">
                        <div v-if="form.dates.length" class="flex flex-wrap gap-1.5 pb-3 border-b border-chalk-200">
                            <span
                                v-for="d in form.dates"
                                :key="d"
                                class="inline-flex items-center gap-1.5 bg-pitch-50 text-pitch-900 text-xs font-mono px-2.5 py-1 rounded-full"
                            >
                                {{ formatDate(d) }}
                                <button type="button" @click="removeDate(d)" class="text-pitch-900/50 hover:text-pitch-900" aria-label="Remove date">&times;</button>
                            </span>
                        </div>
                        <p v-else class="text-sm text-ink-700/50 pb-3 border-b border-chalk-200">
                            No dates selected yet &mdash; tap dates on the calendar.
                        </p>

                        <div>
                            <label class="field-label">Full name</label>
                            <input v-model="form.full_name" type="text" class="field-input" placeholder="e.g. M. F. Rizwan" />
                            <p v-if="form.errors.full_name" class="text-clay-600 text-xs mt-1">{{ form.errors.full_name }}</p>
                        </div>

                        <div>
                            <label class="field-label">Mobile number</label>
                            <input v-model="form.mobile_number" type="tel" class="field-input" placeholder="e.g. 077 123 4567" />
                            <p v-if="form.errors.mobile_number" class="text-clay-600 text-xs mt-1">{{ form.errors.mobile_number }}</p>
                        </div>

                        <div>
                            <label class="field-label">Purpose of booking</label>
                            <input v-model="form.purpose" type="text" class="field-input" placeholder="e.g. Inter-house cricket practice" />
                            <p v-if="form.errors.purpose" class="text-clay-600 text-xs mt-1">{{ form.errors.purpose }}</p>
                        </div>

                        <div class="pt-3 border-t border-chalk-200 flex items-center justify-between">
                            <span class="text-sm text-ink-700/70">Total</span>
                            <span class="font-display font-semibold text-lg text-pitch-900">
                                Rs. {{ totalAmount.toLocaleString() }}
                            </span>
                        </div>

                        <button type="submit" class="btn-primary w-full" :disabled="form.processing || form.dates.length === 0">
                            {{ form.processing ? 'Submitting...' : 'Request booking' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
