<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { BookOpenText, Eye, ReceiptText, Search, XCircle } from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import HoldCountdown from '@/components/HoldCountdown.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatDate, formatPKR, paxSummary } from '@/lib/format';
import bookings from '@/routes/bookings';
import groups from '@/routes/groups';
import payments from '@/routes/payments';
import type { BookingRow, Paginated } from '@/types/portal';

const props = defineProps<{
    bookings: Paginated<BookingRow>;
    filters: { status?: string; search?: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'My bookings', href: bookings.index() }],
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
            bookings.index.url(),
            {
                status: filters.status || undefined,
                search: filters.search || undefined,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }, 300);
});

// Releasing a running hold from the list, after a confirmation.
const releasing = ref<BookingRow | null>(null);
const processing = ref(false);
const error = ref<string>();
const releaseOpen = computed({
    get: () => releasing.value !== null,
    set: (value) => {
        if (!value) {
            releasing.value = null;
            error.value = undefined;
        }
    },
});

function release() {
    if (!releasing.value) {
        return;
    }
    router.post(
        bookings.cancel.url(releasing.value.id),
        {},
        {
            preserveScroll: true,
            onStart: () => (processing.value = true),
            onFinish: () => (processing.value = false),
            onSuccess: () => (releasing.value = null),
            onError: (errors) => (error.value = Object.values(errors)[0]),
        },
    );
}
</script>

<template>
    <Head title="My bookings" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            eyebrow="Agency"
            title="My bookings"
            description="Every hold and confirmed booking your agency has made."
        >
            <Button as-child>
                <Link :href="groups.index()"><Search /> Find groups</Link>
            </Button>
        </PageHeader>

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
            <div class="relative w-full sm:w-72">
                <Search
                    class="absolute top-2.5 left-2.5 size-4 text-muted-foreground"
                />
                <Input
                    v-model="filters.search"
                    placeholder="Reference, PNR or passenger"
                    class="pl-8"
                />
            </div>
        </div>

        <div class="panel overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Route</th>
                        <th>Passengers</th>
                        <th class="text-right">Total</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="booking in props.bookings.data"
                        :key="booking.id"
                        class="cursor-pointer"
                        @click="router.visit(bookings.show(booking.id))"
                    >
                        <td>
                            <Link
                                :href="bookings.show(booking.id)"
                                class="font-mono font-bold hover:text-highlight"
                                @click.stop
                                >{{ booking.reference }}</Link
                            >
                            <p
                                v-if="booking.pnr"
                                class="font-mono text-xs text-muted-foreground"
                            >
                                PNR {{ booking.pnr }}
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
                            </div>
                        </td>
                        <td @click.stop>
                            <div class="flex items-center justify-end gap-1.5">
                                <!-- Paying opens the payment form on this booking. -->
                                <Button
                                    v-if="booking.status === 'on_hold'"
                                    size="sm"
                                    as-child
                                >
                                    <Link
                                        :href="
                                            payments.index({
                                                query: { booking: booking.id },
                                            })
                                        "
                                        ><ReceiptText /> Pay</Link
                                    >
                                </Button>
                                <Button
                                    v-if="booking.seconds_left !== null"
                                    size="sm"
                                    variant="outline"
                                    class="text-destructive hover:text-destructive"
                                    @click="releasing = booking"
                                    ><XCircle /> Release</Button
                                >
                                <Button size="sm" variant="outline" as-child>
                                    <Link :href="bookings.show(booking.id)"
                                        ><Eye /> View</Link
                                    >
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            <EmptyState
                v-if="props.bookings.data.length === 0"
                :icon="BookOpenText"
                :title="
                    filters.status || filters.search
                        ? 'No bookings match'
                        : 'No bookings yet'
                "
                description="Find a group and hold seats; your bookings will be listed here."
            />
        </div>

        <Pagination
            :links="props.bookings.links"
            :from="props.bookings.from"
            :to="props.bookings.to"
            :total="props.bookings.total"
        />

        <ConfirmDialog
            v-model:open="releaseOpen"
            :title="`Release ${releasing?.reference ?? ''}?`"
            description="The booking is cancelled and its seats go back on sale. This can't be undone."
            confirm-label="Release seats"
            destructive
            :processing="processing"
            :error="error"
            @confirm="release"
        />
    </div>
</template>
