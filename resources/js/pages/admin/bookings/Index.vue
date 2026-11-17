<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, Ticket } from '@lucide/vue';
import { reactive, watch } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import HoldCountdown from '@/components/HoldCountdown.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Input } from '@/components/ui/input';
import {
    formatDate,
    formatDateTime,
    formatPKR,
    paxSummary,
} from '@/lib/format';
import admin from '@/routes/admin';
import type { BookingRow, Paginated } from '@/types/portal';

const props = defineProps<{
    bookings: Paginated<BookingRow>;
    filters: { status?: string; search?: string; group?: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Bookings', href: admin.bookings.index() }],
    },
});

const tabs = [
    { value: '', label: 'All' },
    { value: 'on_hold', label: 'On hold' },
    { value: 'confirmed', label: 'Confirmed' },
    { value: 'expired', label: 'Expired' },
    { value: 'cancelled', label: 'Cancelled' },
];

const filters = reactive({
    status: props.filters.status ?? '',
    search: props.filters.search ?? '',
});

let timer: ReturnType<typeof setTimeout> | undefined;
watch(filters, () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get(
            admin.bookings.index.url(),
            {
                status: filters.status || undefined,
                search: filters.search || undefined,
                group: props.filters.group || undefined,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }, 300);
});
</script>

<template>
    <Head title="Bookings" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            eyebrow="Daily work"
            title="Bookings"
            description="Every agency booking. Running holds come first: confirm them once paid."
        />

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div
                class="flex gap-1 overflow-x-auto rounded-full border bg-card p-1"
            >
                <button
                    v-for="tab in tabs"
                    :key="tab.value"
                    type="button"
                    class="rounded-full px-3.5 py-1.5 text-sm font-semibold whitespace-nowrap transition"
                    :class="
                        filters.status === tab.value
                            ? 'bg-ink text-cream dark:bg-gold dark:text-night'
                            : 'text-muted-foreground hover:text-foreground'
                    "
                    @click="filters.status = tab.value"
                >
                    {{ tab.label }}
                </button>
            </div>
            <div class="relative w-full sm:w-80">
                <Search
                    class="absolute top-2.5 left-2.5 size-4 text-muted-foreground"
                />
                <Input
                    v-model="filters.search"
                    placeholder="Reference, PNR, agency or passenger"
                    class="pl-8"
                />
            </div>
        </div>

        <div class="panel overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Agency</th>
                        <th>Route</th>
                        <th>Passengers</th>
                        <th class="text-right">Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="booking in props.bookings.data"
                        :key="booking.id"
                        class="cursor-pointer"
                        @click="router.visit(admin.bookings.show(booking.id))"
                    >
                        <td class="whitespace-nowrap">
                            <Link
                                :href="admin.bookings.show(booking.id)"
                                class="font-mono font-bold hover:text-highlight"
                                @click.stop
                                >{{ booking.reference }}</Link
                            >
                            <p class="text-xs text-muted-foreground">
                                {{ formatDateTime(booking.created_at) }}
                            </p>
                        </td>
                        <td>
                            <p class="font-semibold">
                                {{ booking.agency?.name }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ booking.agency?.city }}
                            </p>
                        </td>
                        <td class="whitespace-nowrap">
                            <p class="font-semibold">{{ booking.sector }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ formatDate(booking.departure_date) }}
                            </p>
                        </td>
                        <td class="text-muted-foreground">
                            {{
                                paxSummary(
                                    booking.adults,
                                    booking.children,
                                    booking.infants,
                                )
                            }}
                        </td>
                        <td
                            class="text-right font-semibold whitespace-nowrap tabular-nums"
                        >
                            {{ formatPKR(booking.total_amount) }}
                        </td>
                        <td>
                            <div class="flex flex-wrap items-center gap-1.5">
                                <StatusBadge :status="booking.status" />
                                <HoldCountdown
                                    v-if="booking.seconds_left !== null"
                                    :seconds="booking.seconds_left"
                                />
                                <span
                                    v-if="booking.pnr"
                                    class="font-mono text-xs text-muted-foreground"
                                    >{{ booking.pnr }}</span
                                >
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            <EmptyState
                v-if="props.bookings.data.length === 0"
                :icon="Ticket"
                title="No bookings match"
            />
        </div>

        <Pagination
            :links="props.bookings.links"
            :from="props.bookings.from"
            :to="props.bookings.to"
            :total="props.bookings.total"
        />
    </div>
</template>
