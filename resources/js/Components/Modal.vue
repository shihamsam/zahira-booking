<script setup>
defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: '' },
});

const emit = defineEmits(['close']);
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
            <div class="absolute inset-0 bg-ink-900/50" @click="emit('close')"></div>
            <div class="relative w-full sm:max-w-md bg-white rounded-t-2xl sm:rounded-card shadow-xl p-5 sm:p-6">
                <div class="flex items-start justify-between mb-4">
                    <h3 class="font-display font-semibold text-lg text-pitch-900">{{ title }}</h3>
                    <button type="button" class="text-ink-700/60 hover:text-ink-900" @click="emit('close')" aria-label="Close">
                        &times;
                    </button>
                </div>
                <slot />
            </div>
        </div>
    </Teleport>
</template>
