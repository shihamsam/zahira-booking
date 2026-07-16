<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import Calendar from '@/Components/Calendar.vue';

const props = defineProps({
    resource:               { type: Object, required: true },
    slots:                  { type: Object, default: () => ({}) },
    unavailableDatesBySlot: { type: Object, default: () => ({}) },
    bookingWindow:          { type: Object, required: true },
});

// ── Slot helpers ──────────────────────────────────────────────────────────────

const slotKeys    = computed(() => Object.keys(props.slots));
const isAzwarHall = computed(() => props.resource.slug === 'azwar-hall');
const isNightSlot = computed(() => ['night_4lights', 'night_2lights'].includes(form.slot_type));

// Pre-select the first available slot
const defaultSlot = computed(() => slotKeys.value[0] ?? 'full_day');

// ── Form ──────────────────────────────────────────────────────────────────────

const form = useForm({
    full_name:               '',
    mobile_number:           '',
    nic:                     '',
    email:                   '',
    purpose:                 '',
    slot_type:               defaultSlot.value,
    start_time:              '',
    end_time:                '',
    hours:                   '',
    chair_count:             '',
    sound_system_requested:  false,
    dates:                   [],
    receipt_file:            null,
});

// Keep slot_type in sync if slotKeys changes on mount
watch(defaultSlot, (val) => { if (!form.slot_type) form.slot_type = val; }, { immediate: true });

// When slot changes, clear selected dates (they may be unavailable for the new slot)
watch(() => form.slot_type, () => {
    form.dates = [];
    if (!isNightSlot.value) {
        form.start_time = '';
        form.end_time   = '';
        form.hours      = '';
    } else {
        const cfg = props.slots[form.slot_type];
        form.start_time = cfg?.default_start ?? '';
        form.end_time   = cfg?.default_end   ?? '';
        recalcHours();
    }
});

// ── Unavailable dates for the currently selected slot ─────────────────────────

const currentUnavailableDates = computed(
    () => props.unavailableDatesBySlot[form.slot_type] ?? []
);

// ── Hours calculation ─────────────────────────────────────────────────────────

function timeToMinutes(t) {
    if (!t) return 0;
    const [h, m] = t.split(':').map(Number);
    return h * 60 + m;
}

function recalcHours() {
    if (!isNightSlot.value || !form.start_time || !form.end_time) {
        form.hours = '';
        return;
    }
    let start = timeToMinutes(form.start_time);
    let end   = timeToMinutes(form.end_time);
    // Overnight: end is on next day
    if (end <= start) end += 24 * 60;
    form.hours = String(Math.ceil((end - start) / 60));
}

watch([() => form.start_time, () => form.end_time], recalcHours);

// ── Pricing calculation ───────────────────────────────────────────────────────

const slotConfig = computed(() => props.slots[form.slot_type] ?? null);

const unitPrice = computed(() => {
    if (!slotConfig.value) return Number(props.resource.price_per_day);
    if (slotConfig.value.type === 'hourly') {
        return slotConfig.value.rate * Math.max(1, Number(form.hours) || 0);
    }
    return slotConfig.value.rate;
});

const chairTotal = computed(() => {
    if (!isAzwarHall.value) return 0;
    return (Number(form.chair_count) || 0) * 10; // Rs. 10 per chair
});

const totalAmount = computed(() => {
    return form.dates.length * unitPrice.value + chairTotal.value;
});

// ── Helpers ───────────────────────────────────────────────────────────────────

function formatDate(d) {
    return new Date(d + 'T00:00:00').toLocaleDateString('en-GB', {
        day: 'numeric', month: 'short', year: 'numeric',
    });
}

function removeDate(d) {
    form.dates = form.dates.filter((x) => x !== d);
}

function onReceiptChange(e) {
    form.receipt_file = e.target.files[0] ?? null;
}

// ── Submit ────────────────────────────────────────────────────────────────────

function submit() {
    form.post(`/grounds/${props.resource.slug}/bookings`, {
        forceFormData: true,
    });
}
</script>

<template>
    <PublicLayout>
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-8 sm:py-12">
            <p class="font-mono text-xs uppercase tracking-widest text-pitch-600 mb-2">Facility</p>
            <h1 class="font-display font-bold text-2xl sm:text-3xl text-pitch-900 mb-2">{{ resource.name }}</h1>
            <p class="text-ink-700/70 max-w-xl mb-8">{{ resource.description }}</p>

            <!-- Slot selector (shown when more than one slot type exists) -->
            <div v-if="slotKeys.length > 1" class="mb-6">
                <h2 class="font-display font-semibold uppercase tracking-wide text-sm text-pitch-900 mb-3">
                    1. Choose a time slot
                </h2>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="(cfg, key) in slots"
                        :key="key"
                        type="button"
                        @click="form.slot_type = key"
                        class="px-4 py-2 rounded-full border text-sm font-medium transition-colors"
                        :class="form.slot_type === key
                            ? 'bg-pitch-900 text-chalk-50 border-pitch-900'
                            : 'bg-white text-ink-700 border-chalk-300 hover:border-pitch-900'"
                    >
                        {{ cfg.label }}
                        <span class="ml-1 opacity-60 text-xs">
                            Rs. {{ cfg.rate.toLocaleString() }}{{ cfg.type === 'hourly' ? '/hr' : '' }}
                        </span>
                    </button>
                </div>
                <p v-if="form.errors.slot_type" class="text-clay-600 text-xs mt-1">{{ form.errors.slot_type }}</p>
            </div>

            <!-- Nighttime hours picker -->
            <div v-if="isNightSlot" class="mb-6 card p-4">
                <h2 class="font-display font-semibold uppercase tracking-wide text-sm text-pitch-900 mb-3">
                    Session hours
                </h2>
                <div class="grid sm:grid-cols-3 gap-3">
                    <div>
                        <label class="field-label">Start time</label>
                        <input v-model="form.start_time" type="time" class="field-input" />
                        <p v-if="form.errors.start_time" class="text-clay-600 text-xs mt-1">{{ form.errors.start_time }}</p>
                    </div>
                    <div>
                        <label class="field-label">End time</label>
                        <input v-model="form.end_time" type="time" class="field-input" />
                        <p v-if="form.errors.end_time" class="text-clay-600 text-xs mt-1">{{ form.errors.end_time }}</p>
                    </div>
                    <div>
                        <label class="field-label">Duration (hours)</label>
                        <input v-model="form.hours" type="number" min="1" max="12" class="field-input" readonly />
                        <p v-if="form.errors.hours" class="text-clay-600 text-xs mt-1">{{ form.errors.hours }}</p>
                    </div>
                </div>
                <p class="text-xs text-ink-700/50 mt-2">
                    Rate: Rs. {{ (slotConfig?.rate ?? 0).toLocaleString() }} / hour &times; {{ form.hours || 0 }} hr(s)
                    = Rs. {{ unitPrice.toLocaleString() }} per night
                </p>
            </div>

            <div class="grid lg:grid-cols-5 gap-6 lg:gap-8">
                <!-- Calendar -->
                <div class="lg:col-span-3">
                    <h2 class="font-display font-semibold uppercase tracking-wide text-sm text-pitch-900 mb-3">
                        {{ slotKeys.length > 1 ? (isNightSlot ? '3.' : '2.') : '1.' }} Select your date(s)
                    </h2>
                    <Calendar
                        v-model="form.dates"
                        :unavailable-dates="currentUnavailableDates"
                        :min-date="bookingWindow.from"
                        :max-date="bookingWindow.to"
                    />
                    <p v-if="form.errors.dates" class="text-clay-600 text-sm mt-2">{{ form.errors.dates }}</p>
                </div>

                <!-- Booking form -->
                <div class="lg:col-span-2">
                    <h2 class="font-display font-semibold uppercase tracking-wide text-sm text-pitch-900 mb-3">
                        {{ slotKeys.length > 1 ? (isNightSlot ? '4.' : '3.') : '2.' }} Your details
                    </h2>

                    <form @submit.prevent="submit" class="card p-5 space-y-4">
                        <!-- Selected dates summary -->
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
                            <label class="field-label">NIC number</label>
                            <input v-model="form.nic" type="text" class="field-input" placeholder="e.g. 199012345678" />
                            <p v-if="form.errors.nic" class="text-clay-600 text-xs mt-1">{{ form.errors.nic }}</p>
                        </div>

                        <div>
                            <label class="field-label">Mobile number</label>
                            <input v-model="form.mobile_number" type="tel" class="field-input" placeholder="e.g. 077 123 4567" />
                            <p v-if="form.errors.mobile_number" class="text-clay-600 text-xs mt-1">{{ form.errors.mobile_number }}</p>
                        </div>

                        <div>
                            <label class="field-label">Email <span class="text-ink-700/40 font-normal">(optional — for confirmation)</span></label>
                            <input v-model="form.email" type="email" class="field-input" placeholder="e.g. you@example.com" />
                            <p v-if="form.errors.email" class="text-clay-600 text-xs mt-1">{{ form.errors.email }}</p>
                        </div>

                        <div>
                            <label class="field-label">Purpose of booking</label>
                            <input v-model="form.purpose" type="text" class="field-input" placeholder="e.g. Inter-house cricket practice" />
                            <p v-if="form.errors.purpose" class="text-clay-600 text-xs mt-1">{{ form.errors.purpose }}</p>
                        </div>

                        <!-- Azwar Hall add-ons -->
                        <template v-if="isAzwarHall">
                            <div>
                                <label class="field-label">Number of chairs <span class="text-ink-700/40 font-normal">(Rs. 10 each)</span></label>
                                <input v-model="form.chair_count" type="number" min="0" max="9999" class="field-input" placeholder="0" />
                                <p v-if="form.errors.chair_count" class="text-clay-600 text-xs mt-1">{{ form.errors.chair_count }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <input id="sound-system" v-model="form.sound_system_requested" type="checkbox" class="h-4 w-4 rounded border-chalk-300 text-pitch-900" />
                                <label for="sound-system" class="text-sm text-ink-700 cursor-pointer">
                                    Sound system required <span class="text-ink-700/40">(arranged on request)</span>
                                </label>
                            </div>
                        </template>

                        <!-- Receipt upload -->
                        <div class="pt-3 border-t border-chalk-200">
                            <label class="field-label">Payment receipt <span class="text-clay-600">*</span></label>
                            <p class="text-xs text-ink-700/50 mb-2">
                                Upload your bank deposit receipt (JPG, PNG or PDF, max 5 MB).
                            </p>
                            <input
                                type="file"
                                accept=".jpg,.jpeg,.png,.pdf"
                                @change="onReceiptChange"
                                class="block w-full text-sm text-ink-700 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:bg-chalk-100 file:text-ink-700 file:text-xs hover:file:bg-chalk-200"
                            />
                            <p v-if="form.errors.receipt_file" class="text-clay-600 text-xs mt-1">{{ form.errors.receipt_file }}</p>
                        </div>

                        <!-- Total -->
                        <div class="flex flex-col gap-1 pt-3 border-t border-chalk-200">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-ink-700/70">Total</span>
                                <span class="font-display font-semibold text-lg text-pitch-900">
                                    Rs. {{ totalAmount.toLocaleString() }}
                                </span>
                            </div>
                            <p v-if="isAzwarHall && Number(form.chair_count) > 0" class="text-xs text-ink-700/50 text-right">
                                Hall: Rs. {{ (form.dates.length * (slotConfig?.rate ?? 0)).toLocaleString() }}
                                + Chairs: Rs. {{ chairTotal.toLocaleString() }}
                            </p>
                        </div>

                        <button
                            type="submit"
                            class="btn-primary w-full"
                            :disabled="form.processing || form.dates.length === 0 || !form.receipt_file"
                        >
                            {{ form.processing ? 'Submitting...' : 'Submit booking' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
