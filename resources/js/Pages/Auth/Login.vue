<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    email: '',
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
            <div class="flex flex-col items-center mb-6">
                <span class="w-11 h-11 rounded-full bg-floodlight-500 flex items-center justify-center font-display font-bold text-pitch-900 mb-3">Z</span>
                <h1 class="font-display font-semibold uppercase tracking-wide text-chalk-50">Ground Admin</h1>
            </div>

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
        </div>
    </div>
</template>
