<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { FileText, Printer } from '@lucide/vue';
import { reactive } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { balanceLabel, formatAmount, formatDate } from '@/lib/format';
import type { Statement } from '@/types/portal';

// An agency statement: brought forward, each entry with the running balance, and the closing.
// Debit is charged to the agency; credit is money it paid or got back.
const props = defineProps<{
    statement: Statement;
    filters: { from: string; to: string };
    // Where the date filter reloads (the agency's own page or AMS's view of it).
    url: string;
    bookingHref: (id: number) => NonNullable<InertiaLinkProps['href']>;
}>();

const range = reactive({ ...props.filters });

function print() {
    window.print();
}

function apply() {
    router.get(props.url, range, { preserveScroll: true, replace: true });
}

const typeTone: Record<string, string> = {
    booking:
        'bg-sand text-ink-soft dark:bg-secondary dark:text-muted-foreground',
    payment:
        'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
    refund: 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300',
    adjustment:
        'bg-violet-100 text-violet-800 dark:bg-violet-950 dark:text-violet-300',
};
</script>

<template>
    <div class="flex flex-col gap-4">
        <div
            class="flex flex-wrap items-end justify-between gap-3 print:hidden"
        >
            <form
                class="flex flex-wrap items-end gap-2"
                @submit.prevent="apply"
            >
                <div class="grid gap-1">
                    <Label for="from" class="text-xs">From</Label>
                    <Input
                        id="from"
                        v-model="range.from"
                        type="date"
                        class="w-40"
                    />
                </div>
                <div class="grid gap-1">
                    <Label for="to" class="text-xs">To</Label>
                    <Input
                        id="to"
                        v-model="range.to"
                        type="date"
                        class="w-40"
                    />
                </div>
                <Button type="submit" variant="outline">Show</Button>
            </form>
            <Button variant="ghost" @click="print"><Printer /> Print</Button>
        </div>

        <div class="grid gap-3 sm:grid-cols-4">
            <div class="panel p-4">
                <p class="text-xs font-semibold text-muted-foreground">
                    Brought forward
                </p>
                <p class="mt-1 text-lg font-bold tabular-nums">
                    {{ formatAmount(Math.abs(statement.opening)) }}
                </p>
                <p class="text-xs text-muted-foreground">
                    {{ balanceLabel(statement.opening) }}
                </p>
            </div>
            <div class="panel p-4">
                <p class="text-xs font-semibold text-muted-foreground">
                    Charged (debit)
                </p>
                <p class="mt-1 text-lg font-bold tabular-nums">
                    {{ formatAmount(statement.debit) }}
                </p>
            </div>
            <div class="panel p-4">
                <p class="text-xs font-semibold text-muted-foreground">
                    Paid and credited
                </p>
                <p
                    class="mt-1 text-lg font-bold text-emerald-700 tabular-nums dark:text-emerald-400"
                >
                    {{ formatAmount(statement.credit) }}
                </p>
            </div>
            <div
                class="panel border-gold/40 bg-gradient-to-br from-card to-sand/60 p-4 dark:to-secondary"
            >
                <p class="text-xs font-semibold text-muted-foreground">
                    Closing balance
                </p>
                <p class="mt-1 text-lg font-extrabold tabular-nums">
                    PKR {{ formatAmount(Math.abs(statement.closing)) }}
                </p>
                <p class="text-xs font-semibold text-highlight">
                    {{ balanceLabel(statement.closing) }}
                </p>
            </div>
        </div>

        <div class="panel overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Voucher</th>
                        <th>Details</th>
                        <th class="text-right">Debit</th>
                        <th class="text-right">Credit</th>
                        <th class="text-right">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="bg-muted/30">
                        <td class="whitespace-nowrap">
                            {{ formatDate(filters.from) }}
                        </td>
                        <td></td>
                        <td class="font-semibold">Balance brought forward</td>
                        <td></td>
                        <td></td>
                        <td class="text-right font-semibold tabular-nums">
                            {{ formatAmount(statement.opening) }}
                        </td>
                    </tr>
                    <tr v-for="entry in statement.entries" :key="entry.id">
                        <td class="whitespace-nowrap">
                            {{ formatDate(entry.entry_date) }}
                        </td>
                        <td
                            class="font-mono text-xs whitespace-nowrap text-muted-foreground"
                        >
                            {{ entry.voucher_number }}
                        </td>
                        <td class="min-w-64">
                            <span
                                class="mr-2 inline-block rounded-full px-2 py-0.5 text-[10px] font-bold tracking-wide uppercase"
                                :class="typeTone[entry.type]"
                                >{{ entry.type }}</span
                            >
                            <span>{{ entry.description }}</span>
                            <Link
                                v-if="entry.booking"
                                :href="bookingHref(entry.booking.id)"
                                class="ml-1 text-xs font-semibold text-highlight hover:underline print:hidden"
                                >Open</Link
                            >
                        </td>
                        <td class="text-right whitespace-nowrap tabular-nums">
                            {{ entry.debit ? formatAmount(entry.debit) : '' }}
                        </td>
                        <td
                            class="text-right whitespace-nowrap text-emerald-700 tabular-nums dark:text-emerald-400"
                        >
                            {{ entry.credit ? formatAmount(entry.credit) : '' }}
                        </td>
                        <td
                            class="text-right font-semibold whitespace-nowrap tabular-nums"
                        >
                            {{ formatAmount(entry.balance) }}
                        </td>
                    </tr>
                </tbody>
            </table>
            <EmptyState
                v-if="statement.entries.length === 0"
                :icon="FileText"
                title="No entries in these dates"
                description="Confirmed bookings, approved payments, refunds and adjustments appear here."
            />
        </div>
        <p class="text-xs text-muted-foreground">
            Balances are in PKR. A positive balance is payable to AMS; a
            negative balance is credit held with AMS.
        </p>
    </div>
</template>
