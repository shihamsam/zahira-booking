<script setup>
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: { type: String, default: '' },
    token: { type: String, required: true },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/admin/reset-password', {
        onFinish: () => form.reset('password', 'password_confirmation'),
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

            <!-- Reset password form -->
            <form @submit.prevent="submit" class="card p-6 space-y-4">
                <h1 class="font-display font-bold text-lg text-pitch-900">Reset your password</h1>

                <div>
                    <label class="field-label">Email</label>
                    <input v-model="form.email" type="email" class="field-input" autofocus />
                    <p v-if="form.errors.email" class="text-clay-600 text-xs mt-1">{{ form.errors.email }}</p>
                </div>

                <div>
                    <label class="field-label">New password</label>
                    <input v-model="form.password" type="password" class="field-input" />
                    <p v-if="form.errors.password" class="text-clay-600 text-xs mt-1">{{ form.errors.password }}</p>
                </div>

                <div>
                    <label class="field-label">Confirm new password</label>
                    <input v-model="form.password_confirmation" type="password" class="field-input" />
                </div>

                <button type="submit" class="btn-primary w-full" :disabled="form.processing">
                    {{ form.processing ? 'Resetting...' : 'Reset password' }}
                </button>
            </form>

            <!-- Back to login -->
            <p class="text-center mt-5 text-sm">
                <Link href="/admin/login" class="text-chalk-50/60 hover:text-chalk-50">
                    ← Back to sign in
                </Link>
            </p>

        </div>
    </div>
</template>
