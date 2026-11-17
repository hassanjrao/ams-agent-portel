<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CircleCheck, Eye, Search, Ticket, X, XCircle } from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import HoldCountdown from '@/components/HoldCountdown.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
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
    // The group being looked at, when opened from a group's "Bookings" button.
    group: { id: number; sector: string; departure_date: string } | null;
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

// Quick confirm / cancel from the list; the server re-checks every rule.
const canConfirm = (b: BookingRow) =>
    b.status === 'on_hold' || b.status === 'expired';
const canCancel = (b: BookingRow) =>
    b.status === 'on_hold' || b.status === 'confirmed';

const action = ref<{ booking: BookingRow; kind: 'confirm' | 'cancel' } | null>(
    null,
);
const processing = ref(false);
const error = ref<string>();
const open = computed({
    get: () => action.value !== null,
    set: (value) => {
        if (!value) {
            action.value = null;
        }
    },
});

function ask(booking: BookingRow, kind: 'confirm' | 'cancel') {
    error.value = undefined;
    action.value = { booking, kind };
}

function apply(value: string) {
    if (!action.value) {
        return;
    }
    const { booking, kind } = action.value;
    router.post(
        kind === 'confirm'
            ? admin.bookings.confirm.url(booking.id)
            : admin.bookings.cancel.url(booking.id),
        kind === 'confirm' ? { pnr: value || null } : { reason: value },
        {
            preserveScroll: true,
            onStart: () => (processing.value = true),
            onFinish: () => (processing.value = false),
            onSuccess: () => (action.value = null),
            onError: (errors) => (error.value = Object.values(errors)[0]),
        },
    );
}
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

        <div
            v-if="group"
            class="flex w-fit items-center gap-2 rounded-full border border-gold/40 bg-sand/60 py-1 pr-1 pl-3.5 text-sm dark:bg-secondary"
        >
            <span
                >Group <strong>{{ group.sector }}</strong> ·
                {{ formatDate(group.departure_date) }}</span
            >
            <Button size="icon-sm" variant="ghost" as-child>
                <Link
                    :href="admin.bookings.index()"
                    aria-label="Show all bookings"
                    ><X
                /></Link>
            </Button>
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
                        <th class="text-right">Actions</th>
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
                        <td @click.stop>
                            <div class="flex items-center justify-end gap-1.5">
                                <Button
                                    v-if="canConfirm(booking)"
                                    size="sm"
                                    @click="ask(booking, 'confirm')"
                                    ><CircleCheck /> Confirm</Button
                                >
                                <Button
                                    v-if="canCancel(booking)"
                                    size="sm"
                                    variant="outline"
                                    class="text-destructive hover:text-destructive"
                                    @click="ask(booking, 'cancel')"
                                    ><XCircle /> Cancel</Button
                                >
                                <Button size="sm" variant="outline" as-child>
                                    <Link
                                        :href="admin.bookings.show(booking.id)"
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

        <ConfirmDialog
            v-model:open="open"
            :title="
                action?.kind === 'confirm'
                    ? `Confirm ${action.booking.reference}?`
                    : `Cancel ${action?.booking.reference ?? ''}?`
            "
            :description="
                action?.kind === 'confirm'
                    ? `${formatPKR(action.booking.total_amount)} is charged to ${action.booking.agency?.name}'s statement and the agency is emailed.`
                    : action?.booking.status === 'confirmed'
                      ? 'The seats go back on sale, the amount is credited back to the agency, and it is emailed the reason.'
                      : 'The seats go back on sale and the agency is emailed the reason.'
            "
            :confirm-label="
                action?.kind === 'confirm'
                    ? 'Confirm & charge'
                    : 'Cancel booking'
            "
            :destructive="action?.kind === 'cancel'"
            :input-label="
                action?.kind === 'confirm' ? 'PNR (optional)' : 'Reason'
            "
            :input-placeholder="
                action?.kind === 'confirm'
                    ? 'e.g. 6XK2LP'
                    : 'e.g. Not paid, agency asked to cancel'
            "
            :input-required="action?.kind === 'cancel'"
            :initial-value="action?.booking.pnr ?? ''"
            :processing="processing"
            :error="error"
            @confirm="apply"
        />
    </div>
</template>
