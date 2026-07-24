<script setup>
import { useForm, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    admins: { type: Array, default: () => [] },
});

const page         = usePage();
const isSuperAdmin = computed(() => page.props.auth?.user?.isSuperAdmin ?? false);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/admin/admins', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

function removeAdmin(admin) {
    if (!confirm(`Remove admin account "${admin.name}"?`)) return;
    router.delete(`/admin/admins/${admin.id}`, { preserveScroll: true });
}

function formatDate(d) {
    return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}
</script>

<template>
    <AdminLayout>
        <h1 class="font-display font-bold text-2xl text-pitch-900 mb-6">Admin accounts</h1>

        <div class="grid lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 card overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-chalk-100 text-left text-xs uppercase tracking-wide text-ink-700/60">
                        <tr>
                            <th class="px-5 py-3">Name</th>
                            <th class="px-5 py-3">Email</th>
                            <th class="px-5 py-3">Role</th>
                            <th class="px-5 py-3">Added</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-chalk-200">
                        <tr v-for="admin in admins" :key="admin.id">
                            <td class="px-5 py-3 font-medium">{{ admin.name }}</td>
                            <td class="px-5 py-3">{{ admin.email }}</td>
                            <td class="px-5 py-3">
                                <span
                                    class="text-xs font-medium px-2 py-0.5 rounded-full border"
                                    :class="admin.role === 'super_admin'
                                        ? 'bg-pitch-50 text-pitch-700 border-pitch-300'
                                        : 'bg-chalk-100 text-ink-700/60 border-chalk-200'"
                                >
                                    {{ admin.role === 'super_admin' ? 'Super Admin' : 'Admin' }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-xs text-ink-700/60">{{ formatDate(admin.created_at) }}</td>
                            <td class="px-5 py-3 text-right">
                                <template v-if="admin.id === page.props.auth.user.id">
                                    <span class="text-xs text-ink-700/40">You</span>
                                </template>
                                <template v-else-if="isSuperAdmin && admin.role !== 'super_admin'">
                                    <button @click="removeAdmin(admin)" class="text-xs text-clay-600 hover:underline">
                                        Remove
                                    </button>
                                </template>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="card p-5" v-if="isSuperAdmin">
                <h2 class="font-display font-semibold uppercase tracking-wide text-sm text-pitch-900 mb-4">Add admin</h2>
                <form @submit.prevent="submit" class="space-y-3">
                    <div>
                        <label class="field-label">Name</label>
                        <input v-model="form.name" type="text" class="field-input" />
                        <p v-if="form.errors.name" class="text-clay-600 text-xs mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="field-label">Email</label>
                        <input v-model="form.email" type="email" class="field-input" />
                        <p v-if="form.errors.email" class="text-clay-600 text-xs mt-1">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <label class="field-label">Password</label>
                        <input v-model="form.password" type="password" class="field-input" />
                        <p v-if="form.errors.password" class="text-clay-600 text-xs mt-1">{{ form.errors.password }}</p>
                    </div>
                    <div>
                        <label class="field-label">Confirm password</label>
                        <input v-model="form.password_confirmation" type="password" class="field-input" />
                    </div>
                    <button type="submit" class="btn-primary w-full" :disabled="form.processing">
                        {{ form.processing ? 'Creating...' : 'Create admin' }}
                    </button>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
