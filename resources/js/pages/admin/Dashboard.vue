<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Building2,
    Clock,
    PlaneTakeoff,
    Plus,
    Receipt,
    Ticket,
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { formatPKR } from '@/lib/format';
import admin from '@/routes/admin';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: admin.dashboard() }],
    },
});

defineProps<{
    stats: {
        pendingAgencies: number;
        pendingPayments: number;
        activeHolds: number;
        openGroups: number;
        seatsLeft: number;
        confirmedThisMonth: number;
    };
}>();
</script>

<template>
    <Head title="Admin dashboard" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold tracking-tight">Dashboard</h1>
                <p class="text-sm text-muted-foreground">
                    What needs attention today.
                </p>
            </div>
            <Button as-child>
                <Link :href="admin.groups.create()"><Plus /> New group</Link>
            </Button>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <Link
                :href="admin.agencies.index({ query: { status: 'pending' } })"
                class="rounded-xl border p-5 transition hover:bg-accent"
            >
                <div
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <Building2 class="size-4" /> Agencies waiting for approval
                </div>
                <p class="mt-2 text-3xl font-semibold tabular-nums">
                    {{ stats.pendingAgencies }}
                </p>
            </Link>

            <div class="rounded-xl border p-5">
                <div
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <Receipt class="size-4" /> Payments to review
                </div>
                <p class="mt-2 text-3xl font-semibold tabular-nums">
                    {{ stats.pendingPayments }}
                </p>
            </div>

            <div class="rounded-xl border p-5">
                <div
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <Clock class="size-4" /> Bookings on hold now
                </div>
                <p class="mt-2 text-3xl font-semibold tabular-nums">
                    {{ stats.activeHolds }}
                </p>
            </div>

            <Link
                :href="admin.groups.index()"
                class="rounded-xl border p-5 transition hover:bg-accent"
            >
                <div
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <PlaneTakeoff class="size-4" /> Groups on sale
                </div>
                <p class="mt-2 text-3xl font-semibold tabular-nums">
                    {{ stats.openGroups }}
                </p>
                <p class="text-sm text-muted-foreground">
                    {{ stats.seatsLeft }} seats left to sell
                </p>
            </Link>

            <div class="rounded-xl border p-5">
                <div
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <Ticket class="size-4" /> Confirmed this month
                </div>
                <p class="mt-2 text-3xl font-semibold tabular-nums">
                    {{ formatPKR(stats.confirmedThisMonth) }}
                </p>
            </div>
        </div>
    </div>
</template>
