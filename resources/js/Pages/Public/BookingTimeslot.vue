<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import Calendar from '@/Components/Calendar.vue';

const props = defineProps({
    resource:               { type: Object,  required: true },
    slots:                  { type: Object,  default: () => ({}) },
    unavailableDatesBySlot: { type: Object,  default: () => ({}) },
    bookingWindow:          { type: Object,  required: true },
    initialName:            { type: String,  default: '' },
    initialPhone:           { type: String,  default: '' },
    whatsappNumber:         { type: String,  default: '' },
});

// ── Resource helpers ─────────────────────────────────────────────────────────

const isGround   = computed(() => props.resource.slug === 'zahira-green-ground');
const isAzwarHall = computed(() => props.resource.slug === 'azwar-hall');

// ── Time slot definitions ────────────────────────────────────────────────────
// Daytime for Zahira Green Ground is a single block: 6:00 AM – 6:00 PM (flat rate).
// Night is shown as 12 individual 1-hour tiles: 6:00 PM – 6:00 AM.

const NIGHT_START = 18; // 6 PM
const NIGHT_HOURS = 12; // 12 night slots (18:00–06:00)

function hourLabel(h) {
    const norm = ((h % 24) + 24) % 24;
    const ampm = norm < 12 ? 'AM' : 'PM';
    const disp = norm % 12 === 0 ? 12 : norm % 12;
    return `${disp}:00 ${ampm}`;
}

function slotLabel(h) {
    return `${hourLabel(h)} – ${hourLabel(h + 1)}`;
}

const nightSlots = computed(() => {
    if (!isGround.value) return [];
    return Array.from({ length: NIGHT_HOURS }, (_, i) => ({
        hour: NIGHT_START + i,
        label: slotLabel(NIGHT_START + i),
    }));
});

// ── Date selection ───────────────────────────────────────────────────────────

const selectedDates = ref([]);
const selectedDate  = computed(() => selectedDates.value[0] ?? null);

// For the calendar, merge night_4lights + night_2lights unavailability so both
// light options block the same date (physical constraint).
const unavailableForCalendar = computed(() => {
    if (isGround.value) {
        const daytime = new Set(props.unavailableDatesBySlot['daytime'] ?? []);
        const night4  = new Set(props.unavailableDatesBySlot['night_4lights'] ?? []);
        const night2  = new Set(props.unavailableDatesBySlot['night_2lights'] ?? []);
        // A date is calendar-unavailable only when daytime AND both night
        // options are fully booked (nothing left to book on that date).
        return [...daytime].filter(d => night4.has(d) && night2.has(d));
    }
    if (isAzwarHall.value) {
        return props.unavailableDatesBySlot['full_day'] ?? [];
    }
    return [];
});

// Per-period availability for the selected date.
const daytimeUnavailable = computed(() => {
    if (!selectedDate.value || !isGround.value) return false;
    return (props.unavailableDatesBySlot['daytime'] ?? []).includes(selectedDate.value);
});

// Populated by a fetch when any night booking exists on the selected date.
// Contains hour numbers (18–29) that are already covered by existing bookings.
const bookedNightHours = ref(new Set());
const nightFullyBooked = computed(() => bookedNightHours.value.size >= NIGHT_HOURS);

const fullDayUnavailable = computed(() => {
    if (!selectedDate.value || !isAzwarHall.value) return false;
    return (props.unavailableDatesBySlot['full_day'] ?? []).includes(selectedDate.value);
});

// ── Slot selection ───────────────────────────────────────────────────────────

const daytimeSelected = ref(false);          // single daytime tile toggle
const selectedHours   = ref([]);             // night hour numbers (int)
const lightsOption    = ref('night_4lights');
const chairCount      = ref('');
const soundSystem     = ref(false);

// When the date changes, clear selections and fetch per-hour night availability
// if any night booking already exists on that date.
watch(selectedDate, async (date) => {
    daytimeSelected.value  = false;
    selectedHours.value    = [];
    bookedNightHours.value = new Set();

    if (!date || !isGround.value) return;

    const hasNightBooking =
        (props.unavailableDatesBySlot['night_4lights'] ?? []).includes(date) ||
        (props.unavailableDatesBySlot['night_2lights'] ?? []).includes(date);

    if (!hasNightBooking) return;

    const res  = await fetch(`/facilities/${props.resource.slug}/timeslots?date=${date}`);
    const data = await res.json();
    bookedNightHours.value = new Set(data.bookedNightHours ?? []);
});

function toggleDaytime() {
    if (daytimeSelected.value) {
        daytimeSelected.value = false;
    } else {
        selectedHours.value   = [];
        daytimeSelected.value = true;
    }
}

function toggleHour(hour) {
    daytimeSelected.value = false;
    const idx = selectedHours.value.indexOf(hour);
    if (idx >= 0) {
        selectedHours.value = selectedHours.value.filter(h => h !== hour);
    } else {
        selectedHours.value = [...selectedHours.value, hour].sort((a, b) => a - b);
    }
}

const selectedPeriod = computed(() => {
    if (daytimeSelected.value)      return 'daytime';
    if (selectedHours.value.length) return 'night';
    return null;
});

// ── Derived booking fields from slot selection ────────────────────────────────

const derivedSlotType = computed(() => {
    if (isAzwarHall.value)                  return 'full_day';
    if (selectedPeriod.value === 'daytime') return 'daytime';
    if (selectedPeriod.value === 'night')   return lightsOption.value;
    return null;
});

const derivedStartTime = computed(() => {
    if (selectedPeriod.value !== 'night' || !selectedHours.value.length) return null;
    const h = selectedHours.value[0] % 24;
    return `${String(h).padStart(2, '0')}:00`;
});

const derivedEndTime = computed(() => {
    if (selectedPeriod.value !== 'night' || !selectedHours.value.length) return null;
    const last = selectedHours.value[selectedHours.value.length - 1];
    const h    = ((last + 1) % 24 + 24) % 24;
    return `${String(h).padStart(2, '0')}:00`;
});

const derivedHours = computed(() => {
    if (selectedPeriod.value !== 'night') return 0;
    return selectedHours.value.length;
});

// ── Pricing ──────────────────────────────────────────────────────────────────

const unitPrice = computed(() => {
    const period = selectedPeriod.value;
    if (!period) return 0;
    if (isAzwarHall.value)     return props.slots['full_day']?.rate ?? 0;
    if (period === 'daytime')  return props.slots['daytime']?.rate ?? 0;
    if (period === 'night')    return (props.slots[lightsOption.value]?.rate ?? 0) * Math.max(1, derivedHours.value);
    return 0;
});

const chairTotal = computed(() => {
    if (!isAzwarHall.value) return 0;
    return (Number(chairCount.value) || 0) * 10;
});

const totalAmount = computed(() => unitPrice.value + chairTotal.value);

// ── Selected slot labels (for summary) ───────────────────────────────────────

const selectedSlotLabels = computed(() => {
    if (selectedPeriod.value === 'daytime') return ['6:00 AM – 6:00 PM'];
    return selectedHours.value.map(h => slotLabel(h));
});

// ── Review step ──────────────────────────────────────────────────────────────

const showReview = ref(false);
const agreedToTerms = ref(false);

function formatDate(d) {
    if (!d) return '';
    return new Date(d + 'T00:00:00').toLocaleDateString('en-GB', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
    });
}

// ── Form submission ──────────────────────────────────────────────────────────

const form = useForm({
    full_name:               props.initialName,
    mobile_number:           props.initialPhone,
    slot_type:               '',
    slot_hours:              [],
    start_time:              '',
    end_time:                '',
    hours:                   '',
    chair_count:             '',
    sound_system_requested:  false,
    dates:                   [],
});

function openReview() {
    showReview.value = true;
    agreedToTerms.value = false;
}

function submit() {
    form.slot_type              = derivedSlotType.value;
    form.slot_hours             = selectedHours.value.slice();
    form.start_time             = derivedStartTime.value ?? '';
    form.end_time               = derivedEndTime.value ?? '';
    form.hours                  = String(derivedHours.value || '');
    form.chair_count            = String(chairCount.value || '');
    form.sound_system_requested = soundSystem.value;
    form.dates                  = [selectedDate.value];

    form.post(`/facilities/${props.resource.slug}/bookings`);
}

// ── Guard: can the user proceed to review? ────────────────────────────────────

const canReview = computed(() => {
    if (!selectedDate.value) return false;
    if (isGround.value && !daytimeSelected.value && selectedHours.value.length === 0) return false;
    if (isAzwarHall.value && fullDayUnavailable.value) return false;
    return true;
});
</script>

<template>
    <PublicLayout>
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-8 sm:py-12">

            <!-- Page header -->
            <p class="font-mono text-xs uppercase tracking-widest text-pitch-600 mb-2">Booking</p>
            <h1 class="font-display font-bold text-2xl sm:text-3xl text-pitch-900 mb-1">{{ resource.name }}</h1>
            <p class="text-ink-700/70 max-w-xl mb-8">{{ resource.description }}</p>

            <!-- Two-panel layout -->
            <div class="grid lg:grid-cols-5 gap-8">

                <!-- Left: Calendar -->
                <div class="lg:col-span-2">
                    <h2 class="font-display font-semibold uppercase tracking-wide text-sm text-pitch-900 mb-3">
                        1. Select a date
                    </h2>
                    <Calendar
                        v-model="selectedDates"
                        :unavailable-dates="unavailableForCalendar"
                        :min-date="bookingWindow.from"
                        :max-date="bookingWindow.to"
                        :single-select="true"
                    />
                </div>

                <!-- Right: Time slots + options -->
                <div class="lg:col-span-3">

                    <!-- Placeholder when no date selected -->
                    <div v-if="!selectedDate" class="flex items-center justify-center h-48 rounded-card border-2 border-dashed border-chalk-300 text-ink-700/40 text-sm">
                        Select a date on the calendar to see available time slots.
                    </div>

                    <template v-else>

                        <h2 class="font-display font-semibold uppercase tracking-wide text-sm text-pitch-900 mb-4">
                            2. Select time slot(s) &mdash;
                            <span class="normal-case font-normal text-ink-700/70">{{ formatDate(selectedDate) }}</span>
                        </h2>

                        <!-- ── Zahira Green Ground ── -->
                        <template v-if="isGround">

                            <!-- Daytime — single tile -->
                            <div class="mb-5">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-ink-700/60">Daytime</span>
                                    <span class="text-xs font-mono text-floodlight-600">Rs. {{ (slots['daytime']?.rate ?? 0).toLocaleString() }} flat</span>
                                    <span v-if="daytimeUnavailable" class="text-xs text-clay-600 font-medium">Fully booked</span>
                                </div>
                                <button
                                    type="button"
                                    :disabled="daytimeUnavailable"
                                    @click="toggleDaytime"
                                    class="w-full px-4 py-3 rounded-md border text-sm font-medium text-left transition-colors"
                                    :class="daytimeUnavailable
                                        ? 'bg-chalk-100 text-ink-700/30 line-through border-chalk-200 cursor-not-allowed'
                                        : daytimeSelected
                                            ? 'bg-pitch-900 text-chalk-50 border-pitch-900'
                                            : 'bg-white text-ink-700 border-chalk-300 hover:border-pitch-600 hover:bg-pitch-50'"
                                >
                                    6:00 AM – 6:00 PM
                                </button>
                            </div>

                            <!-- Night — 1-hour tiles -->
                            <div class="mb-4">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-ink-700/60">Night</span>
                                    <span class="text-xs font-mono text-floodlight-600">Rs. {{ (slots[lightsOption]?.rate ?? 0).toLocaleString() }}/hr</span>
                                    <span v-if="nightFullyBooked" class="text-xs text-clay-600 font-medium">Fully booked</span>
                                </div>
                                <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                                    <button
                                        v-for="slot in nightSlots"
                                        :key="slot.hour"
                                        type="button"
                                        :disabled="bookedNightHours.has(slot.hour)"
                                        @click="toggleHour(slot.hour)"
                                        class="px-2 py-2 rounded-md border text-xs font-mono text-center transition-colors"
                                        :class="bookedNightHours.has(slot.hour)
                                            ? 'bg-chalk-100 text-ink-700/30 line-through cursor-not-allowed border-chalk-200'
                                            : selectedHours.includes(slot.hour)
                                                ? 'bg-pitch-900 text-chalk-50 border-pitch-900'
                                                : 'bg-white text-ink-700 border-chalk-300 hover:border-pitch-600 hover:bg-pitch-50'"
                                    >
                                        {{ slot.label }}
                                    </button>
                                </div>
                            </div>

                            <!-- Lights option (shown when night slots selected) -->
                            <div v-if="selectedPeriod === 'night'" class="mb-4 card p-4 bg-pitch-50">
                                <p class="text-xs font-semibold uppercase tracking-wide text-pitch-900 mb-2">Lighting option</p>
                                <div class="flex flex-wrap gap-3">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" v-model="lightsOption" value="night_4lights" class="text-pitch-900" />
                                        <span class="text-sm text-ink-700">
                                            4 Lights
                                            <span class="font-mono text-floodlight-600 ml-1">Rs. {{ (slots['night_4lights']?.rate ?? 0).toLocaleString() }}/hr</span>
                                        </span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" v-model="lightsOption" value="night_2lights" class="text-pitch-900" />
                                        <span class="text-sm text-ink-700">
                                            2 Lights
                                            <span class="font-mono text-floodlight-600 ml-1">Rs. {{ (slots['night_2lights']?.rate ?? 0).toLocaleString() }}/hr</span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </template>

                        <!-- ── Azwar Hall ── -->
                        <template v-if="isAzwarHall">
                            <div class="mb-5">
                                <div
                                    class="px-4 py-3 rounded-md border text-sm font-medium transition-colors"
                                    :class="fullDayUnavailable
                                        ? 'bg-chalk-100 text-ink-700/30 line-through border-chalk-200 cursor-not-allowed'
                                        : 'bg-pitch-900 text-chalk-50 border-pitch-900'"
                                >
                                    Full Day (08:00 AM – 10:00 PM)
                                    <span class="ml-2 font-mono text-sm opacity-70">Rs. {{ (slots['full_day']?.rate ?? 0).toLocaleString() }}</span>
                                </div>
                                <p v-if="fullDayUnavailable" class="text-xs text-clay-600 mt-2">This date is fully booked.</p>
                            </div>

                            <!-- Azwar Hall add-ons -->
                            <div v-if="!fullDayUnavailable" class="card p-4 mb-4 space-y-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-pitch-900">Add-ons</p>
                                <div>
                                    <label class="field-label">Number of chairs <span class="text-ink-700/40 font-normal">(Rs. 10 each)</span></label>
                                    <input v-model="chairCount" type="number" min="0" max="9999" class="field-input" placeholder="0" />
                                </div>
                                <div class="flex items-center gap-3">
                                    <input id="sound-system" v-model="soundSystem" type="checkbox" class="h-4 w-4 rounded border-chalk-300 text-pitch-900" />
                                    <label for="sound-system" class="text-sm text-ink-700 cursor-pointer">
                                        Sound system required <span class="text-ink-700/40">(arranged on request)</span>
                                    </label>
                                </div>
                            </div>
                        </template>

                        <!-- Pricing summary -->
                        <div v-if="(isGround && selectedPeriod) || (isAzwarHall && !fullDayUnavailable)" class="flex items-center justify-between py-3 border-t border-chalk-200 mb-4">
                            <span class="text-sm text-ink-700/70">Estimated total</span>
                            <span class="font-display font-semibold text-lg text-pitch-900">
                                Rs. {{ totalAmount.toLocaleString() }}
                            </span>
                        </div>

                        <!-- Confirm Booking button -->
                        <button
                            v-if="canReview"
                            type="button"
                            class="btn-primary w-full py-3 text-base"
                            @click="openReview"
                        >
                            Confirm Booking
                        </button>

                    </template>
                </div>
            </div>

            <!-- ── Inline review / summary step ───────────────────────────────── -->
            <div
                v-if="showReview"
                class="fixed inset-0 bg-pitch-900/60 z-50 flex items-end sm:items-center justify-center p-4"
                @click.self="showReview = false"
            >
                <div class="bg-white rounded-card w-full max-w-lg p-6 sm:p-8 shadow-2xl max-h-[90vh] overflow-y-auto">

                    <div class="flex items-center justify-between mb-4">
                        <h2 class="font-display font-bold text-lg text-pitch-900">Booking Summary</h2>
                        <button type="button" @click="showReview = false" class="text-ink-700/40 hover:text-ink-700 text-2xl leading-none">&times;</button>
                    </div>

                    <dl class="grid grid-cols-2 gap-y-2 text-sm mb-5">
                        <dt class="text-ink-700/60">Name</dt>
                        <dd class="text-right font-medium">{{ form.full_name }}</dd>

                        <dt class="text-ink-700/60">Phone</dt>
                        <dd class="text-right font-medium">{{ form.mobile_number }}</dd>

                        <dt class="text-ink-700/60">Facility</dt>
                        <dd class="text-right font-medium">{{ resource.name }}</dd>

                        <dt class="text-ink-700/60">Date</dt>
                        <dd class="text-right font-medium">{{ formatDate(selectedDate) }}</dd>

                        <template v-if="isGround && selectedPeriod">
                            <dt class="text-ink-700/60">Time slot(s)</dt>
                            <dd class="text-right font-medium">
                                <span v-for="lbl in selectedSlotLabels" :key="lbl" class="block text-xs font-mono">{{ lbl }}</span>
                            </dd>

                            <template v-if="selectedPeriod === 'night'">
                                <dt class="text-ink-700/60">Lighting</dt>
                                <dd class="text-right font-medium">
                                    {{ lightsOption === 'night_4lights' ? '4 Lights' : '2 Lights' }}
                                </dd>
                            </template>
                        </template>

                        <template v-if="isAzwarHall">
                            <dt class="text-ink-700/60">Time slot</dt>
                            <dd class="text-right font-medium">Full Day</dd>

                            <template v-if="Number(chairCount) > 0">
                                <dt class="text-ink-700/60">Chairs</dt>
                                <dd class="text-right font-medium">{{ chairCount }}</dd>
                            </template>

                            <template v-if="soundSystem">
                                <dt class="text-ink-700/60">Sound system</dt>
                                <dd class="text-right font-medium">Requested</dd>
                            </template>
                        </template>

                        <dt class="text-ink-700/60 font-semibold">Amount to pay</dt>
                        <dd class="text-right font-display font-bold text-pitch-900 text-base">
                            Rs. {{ totalAmount.toLocaleString() }}
                        </dd>
                    </dl>

                    <!-- Booking terms -->
                    <div class="bg-floodlight-500/10 border border-floodlight-500/30 rounded-md p-3 mb-4 text-xs text-ink-700 leading-relaxed">
                        <p class="font-semibold mb-1 text-floodlight-700">Booking Terms</p>
                        Booking once confirmed cannot be cancelled within 24 hours of the slot time.
                    </div>

                    <!-- I agree checkbox -->
                    <div class="flex items-start gap-3 mb-5">
                        <input
                            id="agree-terms"
                            v-model="agreedToTerms"
                            type="checkbox"
                            class="mt-0.5 h-4 w-4 rounded border-chalk-300 text-pitch-900"
                        />
                        <label for="agree-terms" class="text-sm text-ink-700 cursor-pointer">
                            I agree to the terms and conditions
                        </label>
                    </div>

                    <button
                        type="button"
                        class="btn-primary w-full py-3 text-base"
                        :disabled="!agreedToTerms || form.processing"
                        @click="submit"
                    >
                        {{ form.processing ? 'Submitting...' : 'Submit Booking' }}
                    </button>

                    <p v-if="form.errors?.dates || form.errors?.slot_type" class="text-clay-600 text-xs mt-2 text-center">
                        {{ form.errors?.dates || form.errors?.slot_type }}
                    </p>
                </div>
            </div>

        </div>
    </PublicLayout>
</template>
