<script setup>
import { useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    booking: { type: Object, required: true },
});

const SLOT_LABELS = {
    full_day:     'Full Day',
    daytime:      'Daytime (8:30 AM – 6:30 PM)',
    night_4lights:'Night — 4 Lights',
    night_2lights:'Night — 2 Lights',
};

const receiptForm    = useForm({ receipt: null });
const cancelForm     = useForm({ reason: '' });
const rejectForm     = useForm({ reason: '' });
const showCancelModal = ref(false);
const showRejectModal = ref(false);

function formatDate(d) {
    return new Date(d).toLocaleDateString('en-GB', {
        day: 'numeric', month: 'short', year: 'numeric',
    });
}
function formatDateTime(d) {
    return d ? new Date(d).toLocaleString('en-GB', {
        day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
    }) : '-';
}

function onFileChange(e) {
    receiptForm.receipt = e.target.files[0];
}

function uploadReceipt() {
    receiptForm.post(`/admin/bookings/${props.booking.id}/receipt`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => receiptForm.reset(),
    });
}

function confirmBooking() {
    router.post(`/admin/bookings/${props.booking.id}/confirm`, {}, { preserveScroll: true });
}

function submitCancel() {
    cancelForm.post(`/admin/bookings/${props.booking.id}/cancel`, {
        preserveScroll: true,
        onSuccess: () => { showCancelModal.value = false; cancelForm.reset(); },
    });
}

function submitReject() {
    rejectForm.post(`/admin/bookings/${props.booking.id}/reject`, {
        preserveScroll: true,
        onSuccess: () => { showRejectModal.value = false; rejectForm.reset(); },
    });
}

const isActive = (status) => !['cancelled', 'rejected'].includes(status);
</script>

<template>
    <AdminLayout>
        <div class="flex items-start justify-between mb-6 gap-4 flex-wrap">
            <div>
                <p class="font-mono text-xs uppercase tracking-widest text-pitch-600 mb-1">{{ booking.resource.name }}</p>
                <h1 class="font-display font-bold text-2xl text-pitch-900">{{ booking.reference_no }}</h1>
            </div>
            <StatusBadge :status="booking.status" />
        </div>

        <div class="grid lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-5">
                <!-- Booking details -->
                <div class="card p-5">
                    <h2 class="font-display font-semibold uppercase tracking-wide text-sm text-pitch-900 mb-4">Booking details</h2>
                    <dl class="grid grid-cols-2 gap-y-3 text-sm">
                        <dt class="text-ink-700/60">Full name</dt>
                        <dd class="text-right font-medium">{{ booking.full_name }}</dd>

                        <dt class="text-ink-700/60">NIC</dt>
                        <dd class="text-right font-medium">{{ booking.nic ?? '—' }}</dd>

                        <dt class="text-ink-700/60">Mobile number</dt>
                        <dd class="text-right font-medium">{{ booking.mobile_number }}</dd>

                        <template v-if="booking.email">
                            <dt class="text-ink-700/60">Email</dt>
                            <dd class="text-right font-medium">{{ booking.email }}</dd>
                        </template>

                        <dt class="text-ink-700/60">Purpose</dt>
                        <dd class="text-right font-medium">{{ booking.purpose }}</dd>

                        <template v-if="booking.slot_type">
                            <dt class="text-ink-700/60">Slot</dt>
                            <dd class="text-right font-medium">{{ SLOT_LABELS[booking.slot_type] ?? booking.slot_type }}</dd>
                        </template>

                        <template v-if="booking.hours">
                            <dt class="text-ink-700/60">Duration</dt>
                            <dd class="text-right font-medium">
                                {{ booking.hours }} hr(s)
                                <span v-if="booking.start_time" class="text-ink-700/50">
                                    ({{ booking.start_time }} – {{ booking.end_time }})
                                </span>
                            </dd>
                        </template>

                        <dt class="text-ink-700/60">Dates</dt>
                        <dd class="text-right font-medium">
                            <span v-for="d in booking.dates" :key="d.id" class="block">{{ formatDate(d.date) }}</span>
                        </dd>

                        <template v-if="booking.chair_count">
                            <dt class="text-ink-700/60">Chairs</dt>
                            <dd class="text-right font-medium">{{ booking.chair_count }}</dd>
                        </template>

                        <template v-if="booking.sound_system_requested">
                            <dt class="text-ink-700/60">Sound system</dt>
                            <dd class="text-right font-medium">Requested</dd>
                        </template>

                        <dt class="text-ink-700/60">Total amount</dt>
                        <dd class="text-right font-display font-semibold text-pitch-900">
                            Rs. {{ Number(booking.total_amount).toLocaleString() }}
                        </dd>

                        <dt class="text-ink-700/60">Submitted</dt>
                        <dd class="text-right">{{ formatDateTime(booking.created_at) }}</dd>
                    </dl>
                </div>

                <!-- Confirmed card -->
                <div v-if="booking.status === 'confirmed'" class="card p-5">
                    <h2 class="font-display font-semibold uppercase tracking-wide text-sm text-pitch-900 mb-2">Confirmed</h2>
                    <p class="text-sm text-ink-700/70">
                        Confirmed by {{ booking.confirmed_by?.name }} on {{ formatDateTime(booking.confirmed_at) }}
                    </p>
                </div>

                <!-- Cancelled card -->
                <div v-if="booking.status === 'cancelled'" class="card p-5">
                    <h2 class="font-display font-semibold uppercase tracking-wide text-sm text-clay-600 mb-2">Cancelled</h2>
                    <p class="text-sm text-ink-700/70">
                        Cancelled by {{ booking.cancelled_by?.name }} on {{ formatDateTime(booking.cancelled_at) }}
                    </p>
                    <p class="text-sm text-ink-700 mt-1">Reason: {{ booking.cancellation_reason }}</p>
                </div>

                <!-- Rejected card -->
                <div v-if="booking.status === 'rejected'" class="card p-5">
                    <h2 class="font-display font-semibold uppercase tracking-wide text-sm text-clay-700 mb-2">Rejected</h2>
                    <p class="text-sm text-ink-700/70">
                        Rejected by {{ booking.rejected_by?.name }} on {{ formatDateTime(booking.rejected_at) }}
                    </p>
                    <p class="text-sm text-ink-700 mt-1">Reason: {{ booking.rejection_reason }}</p>
                </div>
            </div>

            <!-- Sidebar: receipt + actions -->
            <div class="space-y-5">
                <div class="card p-5">
                    <h2 class="font-display font-semibold uppercase tracking-wide text-sm text-pitch-900 mb-4">
                        Deposit receipt
                    </h2>

                    <template v-if="booking.receipt_path">
                        <a :href="`/storage/${booking.receipt_path}`" target="_blank" class="block mb-4">
                            <img
                                v-if="!booking.receipt_path.endsWith('.pdf')"
                                :src="`/storage/${booking.receipt_path}`"
                                alt="Deposit receipt"
                                class="rounded-md border border-chalk-200 w-full object-cover max-h-64"
                            />
                            <span v-else class="inline-flex items-center gap-2 text-sm text-pitch-600 underline">
                                View PDF receipt
                            </span>
                        </a>
                    </template>
                    <p v-else class="text-sm text-ink-700/50 mb-4">No receipt attached.</p>

                    <form v-if="isActive(booking.status)" @submit.prevent="uploadReceipt" class="space-y-3">
                        <input type="file" accept=".jpg,.jpeg,.png,.pdf" @change="onFileChange" class="text-sm w-full" />
                        <p v-if="receiptForm.errors.receipt" class="text-clay-600 text-xs">{{ receiptForm.errors.receipt }}</p>
                        <button
                            type="submit"
                            class="btn-outline w-full text-xs"
                            :disabled="receiptForm.processing || !receiptForm.receipt"
                        >
                            {{ receiptForm.processing ? 'Uploading...' : (booking.receipt_path ? 'Replace receipt' : 'Upload receipt') }}
                        </button>
                    </form>
                </div>

                <div v-if="isActive(booking.status)" class="card p-5 space-y-3">
                    <h2 class="font-display font-semibold uppercase tracking-wide text-sm text-pitch-900 mb-1">Actions</h2>

                    <button
                        v-if="booking.status === 'pending'"
                        @click="confirmBooking"
                        class="btn-primary w-full"
                        :disabled="!booking.receipt_path"
                    >
                        Confirm booking
                    </button>
                    <p v-if="booking.status === 'pending' && !booking.receipt_path" class="text-xs text-ink-700/50">
                        Attach a receipt before confirming.
                    </p>

                    <button
                        v-if="booking.status === 'pending'"
                        @click="showRejectModal = true"
                        class="btn-outline w-full border-clay-400 text-clay-600 hover:bg-clay-50"
                    >
                        Reject booking
                    </button>

                    <button @click="showCancelModal = true" class="btn-danger w-full">
                        Cancel booking
                    </button>
                </div>
            </div>
        </div>

        <!-- Cancel modal -->
        <Modal :show="showCancelModal" title="Cancel this booking?" @close="showCancelModal = false">
            <p class="text-sm text-ink-700/70 mb-4">
                This frees up the reserved date(s). Please note a reason for the record.
            </p>
            <form @submit.prevent="submitCancel" class="space-y-3">
                <textarea v-model="cancelForm.reason" rows="3" class="field-input" placeholder="e.g. Ground needed for school sports day"></textarea>
                <p v-if="cancelForm.errors.reason" class="text-clay-600 text-xs">{{ cancelForm.errors.reason }}</p>
                <div class="flex gap-2">
                    <button type="button" class="btn-outline flex-1" @click="showCancelModal = false">Keep booking</button>
                    <button type="submit" class="btn-danger flex-1" :disabled="cancelForm.processing">
                        {{ cancelForm.processing ? 'Cancelling...' : 'Confirm cancellation' }}
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Reject modal -->
        <Modal :show="showRejectModal" title="Reject this booking?" @close="showRejectModal = false">
            <p class="text-sm text-ink-700/70 mb-4">
                Use this when the payment receipt is invalid or unverifiable. This frees up the date(s).
            </p>
            <form @submit.prevent="submitReject" class="space-y-3">
                <textarea v-model="rejectForm.reason" rows="3" class="field-input" placeholder="e.g. Receipt photo was unclear, payment not confirmed"></textarea>
                <p v-if="rejectForm.errors.reason" class="text-clay-600 text-xs">{{ rejectForm.errors.reason }}</p>
                <div class="flex gap-2">
                    <button type="button" class="btn-outline flex-1" @click="showRejectModal = false">Go back</button>
                    <button type="submit" class="btn-danger flex-1" :disabled="rejectForm.processing">
                        {{ rejectForm.processing ? 'Rejecting...' : 'Reject booking' }}
                    </button>
                </div>
            </form>
        </Modal>
    </AdminLayout>
</template>
