<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    CalendarCheck,
    Clock,
    PlaneTakeoff,
    ReceiptText,
    Search,
    Wallet,
} from '@lucide/vue';
import { computed } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import GroupCard from '@/components/GroupCard.vue';
import HoldCountdown from '@/components/HoldCountdown.vue';
import StatCard from '@/components/StatCard.vue';
import { Button } from '@/components/ui/button';
import {
    balanceLabel,
    formatDate,
    formatPKR,
    formatWeekday,
} from '@/lib/format';
import { dashboard, ledger } from '@/routes';
import bookings from '@/routes/bookings';
import groups from '@/routes/groups';
import payments from '@/routes/payments';
import type { BookingRow, GroupCard as Group } from '@/types/portal';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

defineProps<{
    holds: BookingRow[];
    upcoming: BookingRow[];
    onSale: Group[];
    stats: {
        holds: number;
        holdsValue: number;
        upcomingTrips: number;
        pendingPayments: number;
    };
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);
const agency = computed(() => page.props.myAgency);
const firstName = computed(() => user.value.name.split(' ')[0]);
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <!-- Welcome banner in the website's night-and-gold style. -->
        <section
            class="relative overflow-hidden rounded-2xl bg-night text-white shadow-lg"
        >
            <img
                src="/images/flights.webp"
                alt=""
                class="absolute inset-0 h-full w-full object-cover opacity-45"
            />
            <div
                class="absolute inset-0 bg-gradient-to-r from-night via-night/85 to-night/20"
            />
            <div
                class="relative flex flex-col gap-5 p-6 sm:p-8 md:flex-row md:items-end md:justify-between"
            >
                <div>
                    <p
                        class="text-[11px] font-bold tracking-[0.22em] text-gold-light uppercase"
                    >
                        {{ agency?.name }}
                    </p>
                    <h1
                        class="mt-2 font-display text-3xl leading-tight font-semibold sm:text-4xl"
                    >
                        Assalam-o-Alaikum,
                        <span class="text-gold-gradient-light">{{
                            firstName
                        }}</span>
                    </h1>
                    <p class="mt-2 max-w-md text-sm text-white/70">
                        Group seats bought by AMS, ready to sell. Hold seats in
                        a few taps, send payment, and track every booking here.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button
                        as-child
                        size="lg"
                        class="bg-gold text-night hover:bg-gold-light"
                    >
                        <Link :href="groups.index()"
                            ><Search /> Find groups</Link
                        >
                    </Button>
                    <Button
                        as-child
                        size="lg"
                        variant="outline"
                        class="border-white/25 bg-white/5 text-white hover:bg-white/15 hover:text-white"
                    >
                        <Link :href="payments.index()"
                            ><ReceiptText /> Send payment</Link
                        >
                    </Button>
                </div>
            </div>
        </section>

        <div class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
            <StatCard
                label="Seats on hold"
                :value="stats.holds"
                :hint="
                    stats.holds
                        ? `${formatPKR(stats.holdsValue)} to pay`
                        : 'Nothing waiting'
                "
                :icon="Clock"
                :href="bookings.index({ query: { status: 'on_hold' } })"
                :attention="stats.holds > 0"
            />
            <StatCard
                label="Upcoming trips"
                :value="stats.upcomingTrips"
                hint="Confirmed bookings yet to fly"
                :icon="CalendarCheck"
                :href="bookings.index({ query: { status: 'confirmed' } })"
            />
            <StatCard
                label="Payments being checked"
                :value="formatPKR(stats.pendingPayments)"
                hint="Credited once AMS approves"
                :icon="ReceiptText"
                :href="payments.index({ query: { status: 'pending' } })"
            />
            <StatCard
                :label="balanceLabel(agency?.balance ?? 0)"
                :value="formatPKR(Math.abs(agency?.balance ?? 0))"
                hint="Your account statement"
                :icon="Wallet"
                :href="ledger()"
            />
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.4fr_1fr]">
            <section class="flex flex-col gap-3">
                <div class="flex items-center justify-between">
                    <h2 class="font-display text-xl font-semibold">
                        On sale now
                    </h2>
                    <Link
                        :href="groups.index()"
                        class="flex items-center gap-1 text-sm font-semibold text-highlight hover:underline"
                        >All groups <ArrowRight class="size-4"
                    /></Link>
                </div>
                <GroupCard
                    v-for="group in onSale"
                    :key="group.id"
                    :group="group"
                />
                <div v-if="onSale.length === 0" class="panel">
                    <EmptyState
                        :icon="PlaneTakeoff"
                        title="No groups on sale right now"
                        description="New seat blocks appear here as soon as AMS publishes them."
                    />
                </div>
            </section>

            <!-- On a phone, holds that are running out come before the groups list. -->
            <div class="order-first flex flex-col gap-6 xl:order-none">
                <section class="panel overflow-hidden">
                    <div
                        class="flex items-center justify-between border-b px-5 py-4"
                    >
                        <h2 class="font-semibold">Holds running out</h2>
                        <Link
                            :href="bookings.index()"
                            class="text-sm font-semibold text-highlight hover:underline"
                            >All bookings</Link
                        >
                    </div>
                    <ul class="divide-y">
                        <li v-for="hold in holds" :key="hold.id">
                            <Link
                                :href="bookings.show(hold.id)"
                                class="flex items-center justify-between gap-3 px-5 py-3.5 transition hover:bg-muted/50"
                            >
                                <div class="min-w-0">
                                    <p class="font-semibold">
                                        {{ hold.sector }}
                                        <span
                                            class="font-mono text-xs font-normal text-muted-foreground"
                                            >{{ hold.reference }}</span
                                        >
                                    </p>
                                    <p class="text-xs text-muted-foreground">
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
                        :icon="Clock"
                        title="No seats on hold"
                        description="When you hold seats, the time left to pay shows here."
                    />
                </section>

                <section class="panel overflow-hidden">
                    <div class="border-b px-5 py-4">
                        <h2 class="font-semibold">Upcoming departures</h2>
                    </div>
                    <ul class="divide-y">
                        <li v-for="trip in upcoming" :key="trip.id">
                            <Link
                                :href="bookings.show(trip.id)"
                                class="flex items-center gap-4 px-5 py-3.5 transition hover:bg-muted/50"
                            >
                                <div
                                    class="flex w-12 shrink-0 flex-col items-center rounded-lg border bg-muted/50 py-1"
                                >
                                    <span
                                        class="text-[10px] font-bold text-highlight uppercase"
                                        >{{
                                            formatWeekday(trip.departure_date)
                                        }}</span
                                    >
                                    <span
                                        class="text-lg leading-none font-extrabold"
                                        >{{
                                            trip.departure_date.slice(8, 10)
                                        }}</span
                                    >
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold">
                                        {{ trip.sector }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ formatDate(trip.departure_date) }} ·
                                        {{ trip.passengers }} pax
                                        <template v-if="trip.pnr"
                                            >· PNR {{ trip.pnr }}</template
                                        >
                                    </p>
                                </div>
                            </Link>
                        </li>
                    </ul>
                    <EmptyState
                        v-if="upcoming.length === 0"
                        :icon="CalendarCheck"
                        title="No confirmed trips yet"
                    />
                </section>
            </div>
        </div>
    </div>
</template>
