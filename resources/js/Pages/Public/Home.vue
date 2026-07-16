<script setup>
import { Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineProps({
    resources: { type: Array, default: () => [] },
});
</script>

<template>
    <PublicLayout>
        <section class="bg-pitch-900 text-chalk-50">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 py-14 sm:py-20">
                <p class="font-mono text-floodlight-500 text-xs tracking-widest uppercase mb-3">Book a ground &middot; Kick off in minutes</p>
                <h1 class="font-display font-bold text-3xl sm:text-5xl leading-tight max-w-2xl">
                    Reserve Zahira's grounds for your next match, practice or event.
                </h1>
                <p class="mt-4 text-chalk-50/70 max-w-xl">
                    Pick your dates, submit your details, and settle payment by bank deposit &mdash;
                    we'll confirm your slot as soon as the receipt is verified.
                </p>
            </div>
        </section>

        <section class="max-w-5xl mx-auto px-4 sm:px-6 py-10 sm:py-14">
            <h2 class="font-display font-semibold uppercase tracking-wide text-pitch-900 mb-5">Available grounds</h2>

            <div class="grid sm:grid-cols-2 gap-5">
                <Link
                    v-for="resource in resources"
                    :key="resource.id"
                    :href="`/grounds/${resource.slug}`"
                    class="card p-5 hover:shadow-md transition-shadow group"
                >
                    <div class="flex items-start justify-between mb-3">
                        <h3 class="font-display font-semibold text-lg text-pitch-900 group-hover:text-pitch-600">
                            {{ resource.name }}
                        </h3>
                        <span class="font-mono text-sm text-floodlight-600 shrink-0 ml-3">
                            Rs. {{ Number(resource.price_per_day).toLocaleString() }}/day
                        </span>
                    </div>
                    <p class="text-sm text-ink-700/80 mb-3">{{ resource.description }}</p>
                    <p v-if="resource.location" class="text-xs text-ink-700/50">{{ resource.location }}</p>
                    <span class="btn-primary mt-4 w-full">Check availability</span>
                </Link>
            </div>

            <p v-if="resources.length === 0" class="text-ink-700/60 text-sm">
                No grounds are open for booking right now. Please check back later.
            </p>
        </section>
    </PublicLayout>
</template>
