<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Check,
    CircleCheck,
    ExternalLink,
    FileText,
    Search,
    X,
} from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { formatDate, formatDateTime, formatPKR } from '@/lib/format';
import admin from '@/routes/admin';
import type { Paginated, PaymentRow } from '@/types/portal';

const props = defineProps<{
    payments: Paginated<PaymentRow>;
    filters: { status: string; search?: string };
    counts: Record<string, number>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Payments', href: admin.payments.index() }],
    },
});

const tabs = [
    { value: 'pending', label: 'To check' },
    { value: 'approved', label: 'Approved' },
    { value: 'rejected', label: 'Rejected' },
];

const filters = reactive({
    status: props.filters.status,
    search: props.filters.search ?? '',
});

let timer: ReturnType<typeof setTimeout> | undefined;
watch(filters, () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get(
            admin.payments.index.url(),
            { status: filters.status, search: filters.search || undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }, 300);
});

// Reviewing one payment: the receipt beside the approve / reject decision.
const reviewing = ref<PaymentRow | null>(null);
const decision = ref<'approve' | 'reject'>('approve');
const form = useForm({ remarks: '' });
const open = computed({
    get: () => reviewing.value !== null,
    set: (value) => {
        if (!value) {
            reviewing.value = null;
        }
    },
});

function review(payment: PaymentRow, choice: 'approve' | 'reject') {
    reviewing.value = payment;
    decision.value = choice;
    form.reset();
    form.clearErrors();
}

function submit() {
    if (!reviewing.value) {
        return;
    }
    const route =
        decision.value === 'approve'
            ? admin.payments.approve(reviewing.value.id)
            : admin.payments.reject(reviewing.value.id);
    form.submit(route, {
        preserveScroll: true,
        onSuccess: () => (reviewing.value = null),
    });
}
</script>

<template>
    <Head title="Payments" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            eyebrow="Daily work"
            title="Payments"
            description="Match each receipt against the bank statement. Approving credits the agency's account; rejecting tells it why."
        />

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div
                class="flex gap-1 overflow-x-auto rounded-full border bg-card p-1"
            >
                <button
                    v-for="tab in tabs"
                    :key="tab.value"
                    type="button"
                    class="flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-sm font-semibold whitespace-nowrap transition"
                    :class="
                        filters.status === tab.value
                            ? 'bg-ink text-cream dark:bg-gold dark:text-night'
                            : 'text-muted-foreground hover:text-foreground'
                    "
                    @click="filters.status = tab.value"
                >
                    {{ tab.label }}
                    <span class="text-xs opacity-70">{{
                        counts[tab.value] ?? 0
                    }}</span>
                </button>
            </div>
            <div class="relative w-full sm:w-72">
                <Search
                    class="absolute top-2.5 left-2.5 size-4 text-muted-foreground"
                />
                <Input
                    v-model="filters.search"
                    placeholder="Agency, note or amount"
                    class="pl-8"
                />
            </div>
        </div>

        <div class="panel overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Agency</th>
                        <th>Paid into</th>
                        <th>For</th>
                        <th class="text-right">Amount</th>
                        <th>Receipt</th>
                        <th class="text-right">
                            {{
                                filters.status === 'pending'
                                    ? 'Decision'
                                    : 'Status'
                            }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="payment in props.payments.data"
                        :key="payment.id"
                    >
                        <td>
                            <Link
                                v-if="payment.agency"
                                :href="admin.agencies.show(payment.agency.id)"
                                class="font-semibold hover:text-highlight"
                                >{{ payment.agency.name }}</Link
                            >
                            <p class="text-xs text-muted-foreground">
                                Sent {{ formatDateTime(payment.created_at) }}
                                <template v-if="payment.submitted_by">
                                    by {{ payment.submitted_by }}</template
                                >
                            </p>
                        </td>
                        <td>
                            <p class="font-semibold">{{ payment.bank }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ formatDate(payment.paid_on) }}
                                <template v-if="payment.description">
                                    · {{ payment.description }}</template
                                >
                            </p>
                        </td>
                        <td>
                            <Link
                                v-if="payment.booking"
                                :href="admin.bookings.show(payment.booking.id)"
                                class="font-mono text-xs font-bold hover:text-highlight"
                                >{{ payment.booking.reference }}</Link
                            >
                            <span v-else class="text-xs text-muted-foreground"
                                >Account</span
                            >
                        </td>
                        <td
                            class="text-right text-base font-bold whitespace-nowrap tabular-nums"
                        >
                            {{ formatPKR(payment.amount) }}
                        </td>
                        <td>
                            <a
                                :href="payment.receipt_url"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center gap-1 text-xs font-semibold text-highlight hover:underline"
                                ><FileText class="size-3.5" />
                                {{ payment.receipt_is_pdf ? 'PDF' : 'Photo' }}
                                <ExternalLink class="size-3"
                            /></a>
                        </td>
                        <td class="text-right">
                            <div
                                v-if="payment.status === 'pending'"
                                class="flex justify-end gap-1.5"
                            >
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="review(payment, 'reject')"
                                    ><X /> Reject</Button
                                >
                                <Button
                                    size="sm"
                                    @click="review(payment, 'approve')"
                                    ><Check /> Approve</Button
                                >
                            </div>
                            <div v-else class="flex flex-col items-end gap-1">
                                <StatusBadge :status="payment.status" />
                                <span
                                    v-if="payment.admin_remarks"
                                    class="max-w-56 text-xs text-muted-foreground"
                                    >{{ payment.admin_remarks }}</span
                                >
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            <EmptyState
                v-if="props.payments.data.length === 0"
                :icon="CircleCheck"
                :title="
                    filters.status === 'pending'
                        ? 'No payments waiting'
                        : 'Nothing here yet'
                "
                :description="
                    filters.status === 'pending'
                        ? 'New receipts from agencies appear here.'
                        : undefined
                "
            />
        </div>

        <Pagination
            :links="props.payments.links"
            :from="props.payments.from"
            :to="props.payments.to"
            :total="props.payments.total"
        />

        <Dialog v-model:open="open">
            <DialogContent v-if="reviewing" class="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>
                        {{ decision === 'approve' ? 'Approve' : 'Reject' }}
                        {{ formatPKR(reviewing.amount) }} from
                        {{ reviewing.agency?.name }}
                    </DialogTitle>
                    <DialogDescription>
                        {{ reviewing.bank }}, paid
                        {{ formatDate(reviewing.paid_on) }}.
                        {{
                            decision === 'approve'
                                ? 'It is credited to the agency’s statement straight away.'
                                : 'Nothing is credited. The agency sees your reason.'
                        }}
                    </DialogDescription>
                </DialogHeader>

                <div class="overflow-hidden rounded-xl border bg-muted/40">
                    <iframe
                        v-if="reviewing.receipt_is_pdf"
                        :src="reviewing.receipt_url"
                        title="Receipt"
                        class="h-80 w-full"
                    />
                    <img
                        v-else
                        :src="reviewing.receipt_url"
                        alt="Payment receipt"
                        class="max-h-80 w-full object-contain"
                    />
                </div>

                <form class="grid gap-1.5" @submit.prevent="submit">
                    <Label for="remarks">{{
                        decision === 'approve'
                            ? 'Note (optional)'
                            : 'Reason for the agency'
                    }}</Label>
                    <Input
                        id="remarks"
                        v-model="form.remarks"
                        :placeholder="
                            decision === 'approve'
                                ? 'e.g. Matched in Meezan statement'
                                : 'e.g. Amount not received in the account'
                        "
                    />
                    <InputError
                        :message="
                            form.errors.remarks ||
                            (form.errors as Record<string, string>).status
                        "
                    />
                </form>

                <DialogFooter class="gap-2">
                    <Button variant="outline" @click="reviewing = null"
                        >Back</Button
                    >
                    <Button
                        :variant="
                            decision === 'approve' ? 'default' : 'destructive'
                        "
                        :disabled="form.processing"
                        @click="submit"
                    >
                        <Spinner v-if="form.processing" />
                        {{
                            decision === 'approve'
                                ? 'Approve and credit'
                                : 'Reject payment'
                        }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
