<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ExternalLink, FileText, ReceiptText, Upload } from '@lucide/vue';
import { onMounted, ref } from 'vue';
import BankAccountCard from '@/components/BankAccountCard.vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import PaymentDialog from '@/components/PaymentDialog.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { formatDate, formatPKR } from '@/lib/format';
import bookingRoutes from '@/routes/bookings';
import payments from '@/routes/payments';
import type { BankAccount, Paginated, PaymentRow } from '@/types/portal';

const props = defineProps<{
    payments: Paginated<PaymentRow>;
    filters: { status?: string; booking?: string };
    bankAccounts: BankAccount[];
    bookings: { id: number; label: string; total_amount: number }[];
    canPay: boolean;
    pendingTotal: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Payments', href: payments.index() }],
    },
});

const tabs = [
    { value: '', label: 'All' },
    { value: 'pending', label: 'Being checked' },
    { value: 'approved', label: 'Approved' },
    { value: 'rejected', label: 'Rejected' },
];

const open = ref(false);
const startOn = ref<number | null>(null);

function setStatus(status: string) {
    router.get(
        payments.index.url(),
        { status: status || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

// Arriving from a booking's "Send payment" opens the form on that booking.
onMounted(() => {
    if (props.filters.booking && props.canPay) {
        startOn.value = Number(props.filters.booking);
        open.value = true;
    }
});

function newPayment() {
    startOn.value = null;
    open.value = true;
}
</script>

<template>
    <Head title="Payments" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            eyebrow="Agency"
            title="Payments"
            description="Transfers you have sent to AMS. Each is credited to your statement once AMS checks it against the bank."
        >
            <Button :disabled="!canPay" @click="newPayment"
                ><Upload /> Send a payment</Button
            >
        </PageHeader>

        <div class="grid items-start gap-6 xl:grid-cols-[1fr_20rem]">
            <div class="flex flex-col gap-4">
                <div
                    class="flex w-fit gap-1 overflow-x-auto rounded-full border bg-card p-1"
                >
                    <button
                        v-for="tab in tabs"
                        :key="tab.value"
                        type="button"
                        class="rounded-full px-3.5 py-1.5 text-sm font-semibold whitespace-nowrap transition"
                        :class="
                            (filters.status ?? '') === tab.value
                                ? 'bg-ink text-cream dark:bg-gold dark:text-night'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                        @click="setStatus(tab.value)"
                    >
                        {{ tab.label }}
                    </button>
                </div>

                <div class="panel overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Paid on</th>
                                <th>Bank</th>
                                <th>For</th>
                                <th class="text-right">Amount</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="payment in props.payments.data"
                                :key="payment.id"
                            >
                                <td class="whitespace-nowrap">
                                    {{ formatDate(payment.paid_on) }}
                                </td>
                                <td>
                                    <p class="font-semibold">
                                        {{ payment.bank }}
                                    </p>
                                    <p
                                        v-if="payment.description"
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{ payment.description }}
                                    </p>
                                </td>
                                <td>
                                    <Link
                                        v-if="payment.booking"
                                        :href="
                                            bookingRoutes.show(
                                                payment.booking.id,
                                            )
                                        "
                                        class="font-mono text-xs font-bold hover:text-highlight"
                                        >{{ payment.booking.reference }}</Link
                                    >
                                    <span
                                        v-else
                                        class="text-xs text-muted-foreground"
                                        >Account</span
                                    >
                                </td>
                                <td
                                    class="text-right font-semibold whitespace-nowrap tabular-nums"
                                >
                                    {{ formatPKR(payment.amount) }}
                                </td>
                                <td>
                                    <StatusBadge :status="payment.status" />
                                    <p
                                        v-if="payment.admin_remarks"
                                        class="mt-1 max-w-56 text-xs text-muted-foreground"
                                    >
                                        {{ payment.admin_remarks }}
                                    </p>
                                </td>
                                <td class="text-right">
                                    <a
                                        :href="payment.receipt_url"
                                        target="_blank"
                                        rel="noopener"
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-highlight hover:underline"
                                        ><FileText class="size-3.5" />
                                        Receipt
                                        <ExternalLink class="size-3"
                                    /></a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <EmptyState
                        v-if="props.payments.data.length === 0"
                        :icon="ReceiptText"
                        title="No payments here"
                        description="When you transfer money to AMS, send the receipt here so it can be credited."
                    />
                </div>

                <Pagination
                    :links="props.payments.links"
                    :from="props.payments.from"
                    :to="props.payments.to"
                    :total="props.payments.total"
                />
            </div>

            <aside class="flex flex-col gap-3">
                <div
                    v-if="pendingTotal"
                    class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200"
                >
                    <strong>{{ formatPKR(pendingTotal) }}</strong> is being
                    checked by AMS.
                </div>
                <h2 class="text-sm font-semibold text-muted-foreground">
                    Pay into
                </h2>
                <BankAccountCard
                    v-for="account in bankAccounts"
                    :key="account.id"
                    :account="account"
                />
                <p
                    v-if="bankAccounts.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    AMS has not listed its bank accounts yet.
                </p>
            </aside>
        </div>

        <PaymentDialog
            v-model:open="open"
            :bank-accounts="bankAccounts"
            :bookings="bookings"
            :booking-id="startOn"
        />
    </div>
</template>
