<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    modelValue:       { type: Array, default: () => [] },
    unavailableDates: { type: Array, default: () => [] },
    bookedDates:      { type: Array, default: () => [] }, // informational amber — still selectable
    minDate:          { type: String, default: null },
    maxDate:          { type: String, default: null },
    singleSelect:     { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const today = new Date();
today.setHours(0, 0, 0, 0);

const viewDate = ref(new Date(today.getFullYear(), today.getMonth(), 1));

const monthLabel = computed(() =>
    viewDate.value.toLocaleDateString('en-GB', { month: 'long', year: 'numeric' })
);

function toKey(d) {
    return [d.getFullYear(), String(d.getMonth() + 1).padStart(2, '0'), String(d.getDate()).padStart(2, '0')].join('-');
}

const unavailableSet = computed(() => new Set(props.unavailableDates));
const bookedSet      = computed(() => new Set(props.bookedDates));
const selectedSet    = computed(() => new Set(props.modelValue));

const minLimit = computed(() => (props.minDate ? new Date(props.minDate + 'T00:00:00') : today));
const maxLimit = computed(() => (props.maxDate ? new Date(props.maxDate + 'T00:00:00') : null));

const weeks = computed(() => {
    const start = new Date(viewDate.value.getFullYear(), viewDate.value.getMonth(), 1);
    const startOffset = (start.getDay() + 6) % 7; // Monday-first
    const gridStart = new Date(start);
    gridStart.setDate(start.getDate() - startOffset);

    const days = [];
    for (let i = 0; i < 42; i++) {
        const d = new Date(gridStart);
        d.setDate(gridStart.getDate() + i);
        days.push(d);
    }

    const result = [];
    for (let i = 0; i < days.length; i += 7) {
        result.push(days.slice(i, i + 7));
    }
    return result;
});

function isDisabled(d) {
    if (d.getMonth() !== viewDate.value.getMonth()) return true;
    if (d < minLimit.value) return true;
    if (maxLimit.value && d > maxLimit.value) return true;
    if (unavailableSet.value.has(toKey(d))) return true;
    return false;
}

function toggle(d) {
    if (isDisabled(d)) return;
    const key = toKey(d);
    if (props.singleSelect) {
        emit('update:modelValue', props.modelValue[0] === key ? [] : [key]);
        return;
    }
    const current = new Set(props.modelValue);
    if (current.has(key)) {
        current.delete(key);
    } else {
        current.add(key);
    }
    emit('update:modelValue', Array.from(current).sort());
}

function prevMonth() {
    viewDate.value = new Date(viewDate.value.getFullYear(), viewDate.value.getMonth() - 1, 1);
}
function nextMonth() {
    viewDate.value = new Date(viewDate.value.getFullYear(), viewDate.value.getMonth() + 1, 1);
}

const weekdayLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
</script>

<template>
    <div class="card p-4 sm:p-5">
        <div class="flex items-center justify-between mb-4">
            <button type="button" @click="prevMonth" class="w-9 h-9 rounded-full flex items-center justify-center text-pitch-900 hover:bg-pitch-50" aria-label="Previous month">
                &larr;
            </button>
            <p class="font-display font-semibold uppercase tracking-wide text-pitch-900">{{ monthLabel }}</p>
            <button type="button" @click="nextMonth" class="w-9 h-9 rounded-full flex items-center justify-center text-pitch-900 hover:bg-pitch-50" aria-label="Next month">
                &rarr;
            </button>
        </div>

        <div class="grid grid-cols-7 gap-1 mb-1">
            <div v-for="d in weekdayLabels" :key="d" class="text-center text-[11px] font-semibold uppercase tracking-wide text-ink-700/60 py-1">
                {{ d }}
            </div>
        </div>

        <div class="grid grid-cols-7 gap-1">
            <template v-for="(week, wi) in weeks" :key="wi">
                <button
                    v-for="d in week"
                    :key="toKey(d)"
                    type="button"
                    :disabled="isDisabled(d)"
                    @click="toggle(d)"
                    class="aspect-square rounded-md text-sm font-mono flex items-center justify-center relative transition-colors"
                    :class="[
                        d.getMonth() !== viewDate.getMonth() ? 'text-transparent pointer-events-none' : '',
                        isDisabled(d) && d.getMonth() === viewDate.getMonth() ? 'bg-chalk-100 text-ink-700/30 line-through cursor-not-allowed' : '',
                        !isDisabled(d) && selectedSet.has(toKey(d)) ? 'bg-pitch-600 text-chalk-50 font-bold' : '',
                        !isDisabled(d) && !selectedSet.has(toKey(d)) && bookedSet.has(toKey(d)) ? 'bg-floodlight-500/20 text-floodlight-600 border border-floodlight-500/40 hover:bg-floodlight-500/30 font-semibold' : '',
                        !isDisabled(d) && !selectedSet.has(toKey(d)) && !bookedSet.has(toKey(d)) ? 'bg-chalk-50 hover:bg-pitch-100 text-ink-900 border border-chalk-200' : '',
                    ]"
                >
                    {{ d.getDate() }}
                </button>
            </template>
        </div>

        <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 mt-4 text-xs text-ink-700/70">
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-pitch-600"></span> Selected</span>
            <span v-if="bookedDates.length" class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-floodlight-500/30 border border-floodlight-500/40"></span> Booked</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-chalk-100 border border-chalk-200 line-through"></span> Unavailable</span>
        </div>
    </div>
</template>
