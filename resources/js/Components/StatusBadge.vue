<script setup>
defineProps({
    status: { type: String, required: true },
});

const styles = {
    pending: 'bg-floodlight-500/15 text-floodlight-600 border-floodlight-500/30',
    confirmed: 'bg-pitch-600/10 text-pitch-600 border-pitch-600/30',
    cancelled: 'bg-clay-500/10 text-clay-600 border-clay-500/30',
};
</script>

<template>
    <span
        class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide"
        :class="styles[status] ?? 'bg-chalk-100 text-ink-700 border-chalk-200'"
    >
        {{ status }}
    </span>
</template>
