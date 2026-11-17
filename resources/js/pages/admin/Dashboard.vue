<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Building2,
    CircleCheck,
    Clock,
    PlaneTakeoff,
    Plus,
    Receipt,
    Ticket,
} from '@lucide/vue';
import EmptyState from '@/components/EmptyState.vue';
import HoldCountdown from '@/components/HoldCountdown.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatCard from '@/components/StatCard.vue';
import { Button } from '@/components/ui/button';
import { formatDate, formatDateTime, formatPKR } from '@/lib/format';
import admin from '@/routes/admin';
import type { BookingRow, PaymentRow } from '@/types/portal';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Overview', href: admin.dashboard() }],
    },
});

defineProps<{
    stats: {
        pendingAgencies: number;
        pendingPayments: number;
        pendingPaymentsValue: number;
        activeHolds: number;
        openGroups: number;
        seatsLeft: number;
        confirmedThisMonth: number;
    };
    holds: BookingRow[];
    payments: PaymentRow[];
}>();

const name = usePage().props.auth.user.name.split(' ')[0];
const today = new Date().toLocaleDateString('en-GB', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
});
</script>

<template>
    <Head title="Overview" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            :eyebrow="today"
            :title="`Good to see you, ${name}`"
            description="Payments to check, holds to confirm and agencies to approve."
        >
            <Button as-child>
                <Link :href="admin.groups.create()"><Plus /> New group</Link>
            </Button>
        </PageHeader>

        <div class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
            <StatCard
                label="Payments to check"
                :value="stats.pendingPayments"
                :hint="
                    stats.pendingPayments
                        ? `${formatPKR(stats.pendingPaymentsValue)} waiting`
                        : 'All caught up'
                "
                :icon="Receipt"
                :href="admin.payments.index()"
                :attention="stats.pendingPayments > 0"
            />
            <StatCard
                label="Bookings on hold"
                :value="stats.activeHolds"
                hint="Waiting for payment and confirmation"
                :icon="Clock"
                :href="admin.bookings.index({ query: { status: 'on_hold' } })"
                :attention="stats.activeHolds > 0"
            />
            <StatCard
                label="Agencies to approve"
                :value="stats.pendingAgencies"
                hint="New sign-ups"
                :icon="Building2"
                :href="admin.agencies.index({ query: { status: 'pending' } })"
                :attention="stats.pendingAgencies > 0"
            />
            <StatCard
                label="Confirmed this month"
                :value="formatPKR(stats.confirmedThisMonth)"
                :hint="`${stats.openGroups} groups on sale · ${stats.seatsLeft} seats left`"
                :icon="Ticket"
                :href="admin.groups.index()"
            />
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="panel overflow-hidden">
                <div
                    class="flex items-center justify-between border-b px-5 py-4"
                >
                    <div>
                        <h2 class="font-semibold">Payments to check</h2>
                        <p class="text-xs text-muted-foreground">
                            Oldest first
                        </p>
                    </div>
                    <Link
                        :href="admin.payments.index()"
                        class="text-sm font-semibold text-highlight hover:underline"
                        >Review all</Link
                    >
                </div>
                <ul class="divide-y">
                    <li v-for="payment in payments" :key="payment.id">
                        <Link
                            :href="admin.payments.index()"
                            class="flex items-center justify-between gap-3 px-5 py-3.5 transition hover:bg-muted/50"
                        >
                            <div class="min-w-0">
                                <p class="truncate font-semibold">
                                    {{ payment.agency?.name }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ payment.bank }} ·
                                    {{ formatDate(payment.paid_on) }}
                                    <template v-if="payment.booking">
                                        ·
                                        {{
                                            payment.booking.reference
                                        }}</template
                                    >
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold tabular-nums">
                                    {{ formatPKR(payment.amount) }}
                                </p>
                                <p class="text-[11px] text-muted-foreground">
                                    {{ formatDateTime(payment.created_at) }}
                                </p>
                            </div>
                        </Link>
                    </li>
                </ul>
                <EmptyState
                    v-if="payments.length === 0"
                    :icon="CircleCheck"
                    title="No payments waiting"
                />
            </section>

            <section class="panel overflow-hidden">
                <div
                    class="flex items-center justify-between border-b px-5 py-4"
                >
                    <div>
                        <h2 class="font-semibold">Holds running out</h2>
                        <p class="text-xs text-muted-foreground">
                            Confirm once paid, before the time runs out
                        </p>
                    </div>
                    <Link
                        :href="
                            admin.bookings.index({
                                query: { status: 'on_hold' },
                            })
                        "
                        class="text-sm font-semibold text-highlight hover:underline"
                        >All holds</Link
                    >
                </div>
                <ul class="divide-y">
                    <li v-for="hold in holds" :key="hold.id">
                        <Link
                            :href="admin.bookings.show(hold.id)"
                            class="flex items-center justify-between gap-3 px-5 py-3.5 transition hover:bg-muted/50"
                        >
                            <div class="min-w-0">
                                <p class="truncate font-semibold">
                                    {{ hold.agency?.name }}
                                    <span
                                        class="font-mono text-xs font-normal text-muted-foreground"
                                        >{{ hold.reference }}</span
                                    >
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ hold.sector }} ·
                                    {{ formatDate(hold.departure_date) }} ·
                                    {{ hold.passengers }} pax ·
                                    {{ formatPKR(hold.total_amount) }}
                                </p>
                            </div>
                            <HoldCountdown
                                v-if="hold.seconds_left !== null"
                                :seconds="hold.seconds_left"
                            />
                        </Link>
                    </li>
                </ul>
                <EmptyState
                    v-if="holds.length === 0"
                    :icon="PlaneTakeoff"
                    title="No seats on hold"
                />
            </section>
        </div>
    </div>
</template>
