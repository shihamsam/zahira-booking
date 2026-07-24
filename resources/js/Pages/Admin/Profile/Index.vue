<script setup>
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    user: { type: Object, required: true },
});

const form = useForm({
    current_password:      '',
    password:              '',
    password_confirmation: '',
});

function submit() {
    form.put('/admin/profile/password', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <AdminLayout>
        <h1 class="font-display font-bold text-2xl text-pitch-900 mb-6">My Account</h1>

        <div class="grid lg:grid-cols-3 gap-6">

            <!-- Account info -->
            <div class="card p-5">
                <h2 class="font-display font-semibold uppercase tracking-wide text-sm text-pitch-900 mb-4">Account details</h2>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs text-ink-700/50 uppercase tracking-wide font-medium mb-0.5">Name</dt>
                        <dd class="font-medium text-pitch-900">{{ user.name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-ink-700/50 uppercase tracking-wide font-medium mb-0.5">Email</dt>
                        <dd class="font-medium text-pitch-900">{{ user.email }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-ink-700/50 uppercase tracking-wide font-medium mb-0.5">Role</dt>
                        <dd>
                            <span
                                class="text-xs font-medium px-2 py-0.5 rounded-full border"
                                :class="user.role === 'super_admin'
                                    ? 'bg-pitch-50 text-pitch-700 border-pitch-300'
                                    : 'bg-chalk-100 text-ink-700/60 border-chalk-200'"
                            >
                                {{ user.role === 'super_admin' ? 'Super Admin' : 'Admin' }}
                            </span>
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- Change password -->
            <div class="card p-5 lg:col-span-2">
                <h2 class="font-display font-semibold uppercase tracking-wide text-sm text-pitch-900 mb-4">Change password</h2>

                <form @submit.prevent="submit" class="space-y-4 max-w-sm">
                    <div>
                        <label class="field-label">Current password</label>
                        <input
                            v-model="form.current_password"
                            type="password"
                            class="field-input"
                            autocomplete="current-password"
                        />
                        <p v-if="form.errors.current_password" class="text-clay-600 text-xs mt-1">
                            {{ form.errors.current_password }}
                        </p>
                    </div>

                    <div>
                        <label class="field-label">New password</label>
                        <input
                            v-model="form.password"
                            type="password"
                            class="field-input"
                            autocomplete="new-password"
                        />
                        <p v-if="form.errors.password" class="text-clay-600 text-xs mt-1">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <div>
                        <label class="field-label">Confirm new password</label>
                        <input
                            v-model="form.password_confirmation"
                            type="password"
                            class="field-input"
                            autocomplete="new-password"
                        />
                    </div>

                    <button
                        type="submit"
                        class="btn-primary"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Updating…' : 'Update password' }}
                    </button>
                </form>
            </div>

        </div>
    </AdminLayout>
</template>
