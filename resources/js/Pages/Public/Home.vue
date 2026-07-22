<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    resources: { type: Array, default: () => [] },
});

const fullName     = ref('');
const mobileNumber = ref('');
const selectedId   = ref(null);

const selectedResource = () => props.resources.find(r => r.id === selectedId.value) ?? null;

function proceed() {
    const resource = selectedResource();
    if (!resource) return;
    router.visit(`/facilities/${resource.slug}/book`, {
        method: 'get',
        data: { name: fullName.value.trim(), phone: mobileNumber.value.trim() },
    });
}
</script>

<template>
    <PublicLayout>
        <!-- Hero -->
        <section class="bg-pitch-900 text-chalk-50">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 py-14 sm:py-20">
                <p class="font-mono text-floodlight-500 text-xs tracking-widest uppercase mb-3">Zahira College &middot; Book a facility online</p>
                <h1 class="font-display font-bold text-3xl sm:text-4xl leading-tight max-w-2xl">
                    Reserve Zahira Green Ground or Azwar Hall for your next match, event or function.
                </h1>
                <p class="mt-4 text-chalk-50/70 max-w-xl">
                    Enter your details, choose a facility, and pick your time slot.
                </p>
            </div>
        </section>

        <section class="max-w-5xl mx-auto px-4 sm:px-6 py-10 sm:py-14">

            <!-- Step 1: Contact details -->
            <div class="mb-10">
                <h2 class="font-display font-semibold uppercase tracking-wide text-pitch-900 mb-4">
                    1. Your details
                </h2>
                <div class="grid sm:grid-cols-2 gap-4 max-w-xl">
                    <div>
                        <label class="field-label">Full name</label>
                        <input
                            v-model="fullName"
                            type="text"
                            class="field-input"
                            placeholder="e.g. M. F. Rizwan"
                        />
                    </div>
                    <div>
                        <label class="field-label">Phone number</label>
                        <input
                            v-model="mobileNumber"
                            type="tel"
                            class="field-input"
                            placeholder="e.g. 077 123 4567"
                        />
                    </div>
                </div>
            </div>

            <!-- Step 2: Choose facility -->
            <div class="mb-10">
                <h2 class="font-display font-semibold uppercase tracking-wide text-pitch-900 mb-4">
                    2. Choose a facility
                </h2>

                <div class="grid sm:grid-cols-2 gap-5">
                    <button
                        v-for="resource in resources"
                        :key="resource.id"
                        type="button"
                        @click="selectedId = resource.id"
                        class="card p-5 text-left transition-all"
                        :class="selectedId === resource.id
                            ? 'ring-2 ring-pitch-900 shadow-md'
                            : 'hover:shadow-md'"
                    >
                        <div class="flex items-start justify-between mb-3">
                            <h3 class="font-display font-semibold text-lg text-pitch-900">
                                {{ resource.name }}
                            </h3>
                            <span class="font-mono text-sm text-floodlight-600 shrink-0 ml-3">
                                Rs. {{ Number(resource.price_per_day).toLocaleString() }}/day
                            </span>
                        </div>
                        <p class="text-sm text-ink-700/80 mb-3">{{ resource.description }}</p>
                        <p v-if="resource.location" class="text-xs text-ink-700/50 mb-2">{{ resource.location }}</p>

                        <div
                            v-if="selectedId === resource.id"
                            class="mt-2 inline-flex items-center gap-1.5 text-xs font-semibold text-pitch-900 bg-pitch-50 px-2.5 py-1 rounded-full"
                        >
                            <span class="w-2 h-2 rounded-full bg-pitch-600 inline-block"></span>
                            Selected
                        </div>
                    </button>
                </div>

                <p v-if="resources.length === 0" class="text-ink-700/60 text-sm">
                    No facilities are open for booking right now. Please check back later.
                </p>
            </div>

            <!-- Step 3: CTA -->
            <div v-if="selectedId">
                <p v-if="!fullName.trim() || !mobileNumber.trim()" class="text-sm text-ink-700/60 mb-3">
                    Please enter your name and phone number above to continue.
                </p>
                <button
                    v-else
                    type="button"
                    class="btn-primary px-8 py-3 text-base"
                    @click="proceed"
                >
                    Choose Your Time Slot &rarr;
                </button>
            </div>

        </section>
    </PublicLayout>
</template>
