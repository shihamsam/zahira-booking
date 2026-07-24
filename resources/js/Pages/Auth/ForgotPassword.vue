<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const form = useForm({
    email: '',
});

const flashSuccess = computed(() => usePage().props.flash?.success);

function submit() {
    form.post('/admin/forgot-password');
}
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-pitch-900 px-4">
        <div class="w-full max-w-sm">

            <!-- Logo -->
            <div class="flex flex-col items-center mb-8">
                <img src="/images/logo.png" alt="Zahira College seal" class="h-20 w-20 object-contain mb-3" />
                <img src="/images/logo-text.png" alt="Zahira College" class="h-10 w-auto object-contain" />
            </div>

            <div class="card p-6 space-y-4">
                <div>
                    <h1 class="font-display font-bold text-lg text-pitch-900">Forgot your password?</h1>
                    <p class="text-sm text-ink-700/70 mt-1">
                        Enter your email address and we'll send you a link to reset your password.
                    </p>
                </div>

                <p v-if="flashSuccess" class="text-sm text-green-700 bg-green-50 rounded-md px-3 py-2">
                    {{ flashSuccess }}
                </p>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="field-label">Email</label>
                        <input v-model="form.email" type="email" class="field-input" autofocus />
                        <p v-if="form.errors.email" class="text-clay-600 text-xs mt-1">{{ form.errors.email }}</p>
                    </div>

                    <button type="submit" class="btn-primary w-full" :disabled="form.processing">
                        {{ form.processing ? 'Sending...' : 'Email password reset link' }}
                    </button>
                </form>
            </div>

            <!-- Back to login -->
            <p class="text-center mt-5 text-sm">
                <Link href="/admin/login" class="text-chalk-50/60 hover:text-chalk-50">
                    ← Back to sign in
                </Link>
            </p>

        </div>
    </div>
</template>
