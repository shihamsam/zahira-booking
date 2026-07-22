<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import LandingLayout from '@/Layouts/LandingLayout.vue';

const props = defineProps({
    resources: { type: Array, default: () => [] },
});

const fullName     = ref('');
const mobileNumber = ref('');
const selectedId   = ref(null);

const nameTouched  = ref(false);
const phoneTouched = ref(false);

const selectedResource = computed(() =>
    props.resources.find(r => r.id === selectedId.value) ?? null
);

const nameError = computed(() =>
    !fullName.value.trim() ? 'Full name is required.' : null
);

const phoneError = computed(() => {
    const v = mobileNumber.value.trim();
    if (!v) return 'Phone number is required.';
    if (!/^0\d{9}$/.test(v)) return 'Enter a valid 10-digit number starting with 0 (e.g. 0771234567).';
    return null;
});

const isValid = computed(() => !nameError.value && !phoneError.value);

const canProceed = computed(() =>
    !!selectedId.value && isValid.value
);

function proceed() {
    nameTouched.value  = true;
    phoneTouched.value = true;
    if (!isValid.value || !selectedResource.value) return;
    router.visit(`/facilities/${selectedResource.value.slug}/book`, {
        method: 'get',
        data: { name: fullName.value.trim(), phone: mobileNumber.value.trim() },
    });
}
</script>

<template>
    <LandingLayout>

        <!-- ═══════════════════════════════════════════════════════════
             LEFT COLUMN — Brand
             Mobile  : compact header strip, no feature list
             Desktop : full-height column with all copy
        ═══════════════════════════════════════════════════════════ -->
        <div class="lg:w-[42%] bg-pitch-900 relative overflow-hidden flex flex-col
                    px-6 py-6
                    lg:px-12 lg:py-10">

            <!-- Ambient glow — desktop only visual effect -->
            <div class="pointer-events-none hidden lg:block absolute -top-24 -left-24 w-72 h-72 rounded-full bg-pitch-400/10 blur-3xl"></div>
            <div class="pointer-events-none hidden lg:block absolute bottom-0 right-0 w-48 h-48 rounded-full bg-floodlight-500/8 blur-2xl"></div>

            <!-- Centred content wrapper — fills available height, centres content vertically and horizontally -->
            <div class="relative flex-1 flex flex-col justify-center items-center text-center">

                <!-- Logo lockup -->
                <div class="flex items-center gap-3 lg:gap-4 mb-5 lg:mb-8">
                    <img
                        src="/images/logo.png"
                        alt="Zahira College seal"
                        class="h-14 w-14 lg:h-[96px] lg:w-[96px] object-contain shrink-0 drop-shadow"
                    />
                    <img
                        src="/images/logo-text.png"
                        alt="Zahira College"
                        class="h-10 lg:h-[72px] w-auto object-contain brightness-0 invert"
                    />
                </div>

                <!-- Headline -->
                <div>
                    <p class="font-mono text-floodlight-400 text-[10px] lg:text-[11px] tracking-[0.2em] uppercase mb-2 lg:mb-3">
                        Online Facility Booking
                    </p>

                    <h1 class="font-display font-bold text-chalk-50
                                leading-[1.5] lg:leading-[1.1]
                                text-[1.15rem] lg:text-[2.1rem]
                                mb-2 lg:mb-4">
                        Reserve a facility.<br/>
                        <span class="text-floodlight-400">Play on your terms.</span>
                    </h1>

                    <p class="text-chalk-50/80 leading-relaxed
                               text-[0.75rem] lg:text-[0.8125rem]
                               max-w-xs
                               mb-0 lg:mb-7">
                        Book Zahira Green Ground or Azwar Hall for your match,
                        tournament, event or function — online, in minutes.
                    </p>
                </div>

                <!-- Feature list — desktop only, text-left override for readability -->
                <ul class="hidden lg:block space-y-3.5 mt-7 text-left w-full max-w-xs">
                    <li
                        v-for="item in [
                            'Live availability — daytime and hourly night slots',
                            'Instant reference number on confirmation',
                            'Upload your payment receipt to finalise',
                        ]"
                        :key="item"
                        class="flex items-start gap-3"
                    >
                        <span class="mt-0.5 w-4 h-4 rounded-full bg-floodlight-500/20 border border-floodlight-400/40 flex items-center justify-center shrink-0">
                            <svg class="w-2 h-2 text-floodlight-400" fill="none" viewBox="0 0 10 10" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M1.5 5l2.5 2.5 4.5-4.5"/>
                            </svg>
                        </span>
                        <span class="text-chalk-50/85 text-[0.75rem] leading-snug">{{ item }}</span>
                    </li>
                </ul>

            </div>

            <!-- Footer tag — pinned to bottom, desktop only -->
            <div class="hidden lg:block pt-5 border-t border-chalk-50/15 text-center">
                <p class="font-mono text-chalk-50/45 text-[11px] tracking-widest uppercase">
                    Zahira College &bull; Est. 1954 &bull; Puttalam, Sri Lanka
                </p>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════
             RIGHT COLUMN — Booking form
             Mobile  : full-width, natural height, single-col inputs
             Desktop : fixed-height column, side-by-side inputs
        ═══════════════════════════════════════════════════════════ -->
        <div class="lg:w-[58%] bg-white flex flex-col
                    overflow-y-auto lg:overflow-hidden
                    px-6 py-7
                    lg:px-12 lg:py-10">

            <div class="w-full max-w-md mx-auto flex flex-col flex-1">

                <!-- Section header -->
                <div class="mb-5 lg:mb-6">
                    <h2 class="font-display font-bold text-pitch-900 leading-tight
                                text-[1.25rem] lg:text-[1.4rem]">
                        Start your booking
                    </h2>
                    <p class="text-ink-700/55 mt-1 text-[0.8125rem]">
                        Fill in your details and choose a facility to continue.
                    </p>
                </div>

                <!-- Step 1 — Contact details
                     Mobile: stacked (grid-cols-1), Desktop: side-by-side (grid-cols-2) -->
                <div class="mb-5">
                    <p class="font-mono text-[11px] tracking-[0.18em] uppercase text-pitch-600 mb-3">
                        Your details
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="field-label !text-[11px]">Full name</label>
                            <input
                                v-model="fullName"
                                type="text"
                                class="field-input !py-2.5 !text-sm"
                                :class="nameTouched && nameError ? '!border-clay-500' : ''"
                                placeholder="e.g. M. F. Rizwan"
                                autocomplete="name"
                                @blur="nameTouched = true"
                            />
                            <p v-if="nameTouched && nameError" class="text-clay-600 text-[11px] mt-1">{{ nameError }}</p>
                        </div>
                        <div>
                            <label class="field-label !text-[11px]">Phone number</label>
                            <input
                                v-model="mobileNumber"
                                type="tel"
                                class="field-input !py-2.5 !text-sm"
                                :class="phoneTouched && phoneError ? '!border-clay-500' : ''"
                                placeholder="e.g. 0771234567"
                                autocomplete="tel"
                                @blur="phoneTouched = true"
                            />
                            <p v-if="phoneTouched && phoneError" class="text-clay-600 text-[11px] mt-1">{{ phoneError }}</p>
                        </div>
                    </div>
                </div>

                <!-- Step 2 — Facility selection -->
                <div class="mb-5 flex-1 flex flex-col">
                    <p class="font-mono text-[11px] tracking-[0.18em] uppercase text-pitch-600 mb-3">
                        Choose a facility
                    </p>

                    <div class="space-y-2.5 flex-1">
                        <button
                            v-for="resource in resources"
                            :key="resource.id"
                            type="button"
                            @click="selectedId = resource.id"
                            class="group w-full text-left rounded-card border-2 bg-white transition-all duration-150 overflow-hidden"
                            :class="selectedId === resource.id
                                ? 'border-pitch-400 shadow-sm'
                                : 'border-chalk-200 hover:border-pitch-400/60 hover:shadow-sm'"
                        >
                            <!-- Card body — background and text colours unchanged regardless of selection -->
                            <div class="px-4 py-3.5 bg-white">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-display font-semibold leading-tight text-pitch-900
                                                   text-[0.875rem] lg:text-[0.9375rem]">
                                            {{ resource.name }}
                                        </h3>
                                        <p class="text-[11px] mt-0.5 leading-relaxed text-ink-700/75 line-clamp-2">
                                            {{ resource.description }}
                                        </p>
                                        <p v-if="resource.location" class="text-[11px] mt-0.5 text-ink-700/55">
                                            {{ resource.location }}
                                        </p>
                                    </div>
                                    <div class="text-right shrink-0 pt-0.5">
                                        <span class="font-mono font-semibold text-[13px] text-floodlight-600">
                                            Rs. {{ Number(resource.price_per_day).toLocaleString() }}
                                        </span>
                                        <span class="block text-[11px] text-ink-700/55">per day</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Selected strip -->
                            <div
                                v-if="selectedId === resource.id"
                                class="flex items-center gap-2 px-4 py-2 bg-white border-t border-chalk-200"
                            >
                                <svg class="w-2.5 h-2.5 text-pitch-400 shrink-0" fill="none" viewBox="0 0 10 10" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M1.5 5l2.5 2.5 4.5-4.5"/>
                                </svg>
                                <span class="font-mono text-[11px] tracking-widest text-pitch-400 uppercase">
                                    Facility selected
                                </span>
                            </div>
                        </button>

                        <p v-if="resources.length === 0" class="text-ink-700/55 text-xs mt-2">
                            No facilities are open for booking right now.
                        </p>
                    </div>
                </div>

                <!-- CTA -->
                <div class="mt-4 lg:mt-auto pt-2">
                    <p
                        v-if="selectedId && !isValid"
                        class="text-[11px] text-ink-700/60 mb-2"
                    >
                        Please fix the errors above to continue.
                    </p>

                    <!-- min-h-[48px] ensures a comfortable tap target on mobile -->
                    <button
                        type="button"
                        @click="proceed"
                        :disabled="!canProceed"
                        class="w-full min-h-[48px] py-3 rounded-card font-display font-bold
                               text-[0.9375rem] tracking-wide transition-all duration-150"
                        :class="canProceed
                            ? 'bg-pitch-900 text-chalk-50 hover:bg-pitch-950 shadow-md hover:shadow-lg active:scale-[0.98]'
                            : 'bg-chalk-100 text-ink-700/40 cursor-not-allowed'"
                    >
                        Choose Your Time Slot &rarr;
                    </button>

                    <!-- Utility links -->
                    <div class="flex items-center justify-center gap-4 mt-5 pb-4 lg:pb-0">
                        <a href="/upload-receipt" class="text-[11px] font-mono text-ink-700/50 hover:text-ink-700/70 uppercase tracking-widest transition-colors">
                            Upload Receipt
                        </a>
                        <span class="text-ink-700/30 text-[11px]">&bull;</span>
                        <a href="/admin/login" class="text-[11px] font-mono text-ink-700/50 hover:text-ink-700/70 uppercase tracking-widest transition-colors">
                            Admin
                        </a>
                    </div>
                </div>

            </div>
        </div>

    </LandingLayout>
</template>
