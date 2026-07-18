<script setup>
import { Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    email:    '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/admin/login', {
        onFinish: () => form.reset('password'),
    });
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

            <!-- Login form -->
            <form @submit.prevent="submit" class="card p-6 space-y-4">
                <div>
                    <label class="field-label">Email</label>
                    <input v-model="form.email" type="email" class="field-input" autofocus />
                    <p v-if="form.errors.email" class="text-clay-600 text-xs mt-1">{{ form.errors.email }}</p>
                </div>

                <div>
                    <label class="field-label">Password</label>
                    <input v-model="form.password" type="password" class="field-input" />
                    <p v-if="form.errors.password" class="text-clay-600 text-xs mt-1">{{ form.errors.password }}</p>
                </div>

                <label class="flex items-center gap-2 text-sm text-ink-700">
                    <input v-model="form.remember" type="checkbox" class="rounded border-chalk-200" />
                    Remember me
                </label>

                <button type="submit" class="btn-primary w-full" :disabled="form.processing">
                    {{ form.processing ? 'Signing in...' : 'Sign in' }}
                </button>
            </form>

            <!-- Back to site -->
            <p class="text-center mt-5 text-sm">
                <Link href="/" class="text-chalk-50/60 hover:text-chalk-50">
                    ← Back to main site
                </Link>
            </p>

        </div>
    </div>
</template>
