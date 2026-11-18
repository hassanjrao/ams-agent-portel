<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Ban,
    Building,
    Clock,
    Handshake,
    Pencil,
    Plus,
    Search,
    Send,
    Ticket,
} from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import NativeSelect from '@/components/NativeSelect.vue';
import Pagination from '@/components/Pagination.vue';
import PaymentWindowDialog from '@/components/PaymentWindowDialog.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatDate, formatPKR, formatWeekday } from '@/lib/format';
import admin from '@/routes/admin';
import type { Option, Paginated } from '@/types/portal';

type GroupRow = {
    id: number;
    sector: string;
    departure_date: string;
    return_date: string | null;
    duration_days: number | null;
    airline: { name: string; code: string };
    group_type: string;
    total_seats: number;
    seats_confirmed: number;
    seats_held: number;
    seats_available: number;
    adult_fare: number;
    hold_minutes: number;
    sourcing: 'direct' | 'outsourced';
    supplier: string | null;
    status: 'draft' | 'published' | 'closed';
};

const props = defineProps<{
    groups: Paginated<GroupRow>;
    filters: {
        type?: string;
        status?: string;
        search?: string;
        past?: string;
        sourcing?: string;
        supplier?: string;
    };
    groupTypes: Option[];
    defaultHoldMinutes: number;
    suppliers: Option[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Groups', href: admin.groups.index() }],
    },
});

const filters = reactive({
    type: props.filters.type ?? '',
    status: props.filters.status ?? '',
    search: props.filters.search ?? '',
    past: props.filters.past === '1',
    sourcing: props.filters.sourcing ?? '',
    supplier: props.filters.supplier ?? '',
});

// A supplier only narrows outsourced groups, so picking direct clears it.
watch(
    () => filters.sourcing,
    (sourcing) => {
        if (sourcing !== 'outsourced') {
            filters.supplier = '';
        }
    },
);

let timer: ReturnType<typeof setTimeout> | undefined;
watch(filters, () => {
    clearTimeout(timer);
    // Wait for typing to pause before reloading the list.
    timer = setTimeout(() => {
        router.get(
            admin.groups.index.url(),
            {
                type: filters.type || undefined,
                status: filters.status || undefined,
                search: filters.search || undefined,
                past: filters.past ? 1 : undefined,
                sourcing: filters.sourcing || undefined,
                supplier: filters.supplier || undefined,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }, 300);
});

function setStatus(group: GroupRow, status: GroupRow['status']) {
    router.patch(
        admin.groups.status.url(group.id),
        { status },
        { preserveScroll: true, onSuccess: () => (closing.value = null) },
    );
}

// The payment window opens in a small dialog instead of the whole group form.
const windowFor = ref<GroupRow | null>(null);
const windowOpen = computed({
    get: () => windowFor.value !== null,
    set: (value) => {
        if (!value) {
            windowFor.value = null;
        }
    },
});

// Closing stops sales, so it asks first; publishing is one click.
const closing = ref<GroupRow | null>(null);
const closeOpen = computed({
    get: () => closing.value !== null,
    set: (value) => {
        if (!value) {
            closing.value = null;
        }
    },
});
</script>

<template>
    <Head title="Groups" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1
                    class="font-display text-2xl font-semibold sm:text-[1.7rem]"
                >
                    Groups
                </h1>
                <p class="text-sm text-muted-foreground">
                    Seat blocks AMS has bought, with what is sold and left.
                </p>
            </div>
            <Button as-child>
                <Link :href="admin.groups.create()"><Plus /> New group</Link>
            </Button>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <div class="relative w-full sm:w-64">
                <Search
                    class="absolute top-2.5 left-2.5 size-4 text-muted-foreground"
                />
                <Input
                    v-model="filters.search"
                    placeholder="Route, flight or PNR"
                    class="pl-8"
                />
            </div>
            <NativeSelect v-model="filters.type" class="w-full sm:w-48">
                <option value="">All categories</option>
                <option
                    v-for="type in groupTypes"
                    :key="type.id"
                    :value="String(type.id)"
                >
                    {{ type.name }}
                </option>
            </NativeSelect>
            <NativeSelect v-model="filters.status" class="w-full sm:w-40">
                <option value="">All statuses</option>
                <option value="draft">Draft</option>
                <option value="published">Published</option>
                <option value="closed">Closed</option>
            </NativeSelect>
            <NativeSelect v-model="filters.sourcing" class="w-full sm:w-44">
                <option value="">Direct &amp; outsourced</option>
                <option value="direct">Direct (AMS)</option>
                <option value="outsourced">Outsourced</option>
            </NativeSelect>
            <NativeSelect
                v-if="filters.sourcing === 'outsourced'"
                v-model="filters.supplier"
                class="w-full sm:w-48"
            >
                <option value="">All suppliers</option>
                <option
                    v-for="supplier in suppliers"
                    :key="supplier.id"
                    :value="String(supplier.id)"
                >
                    {{ supplier.name }}
                </option>
            </NativeSelect>
            <label class="flex items-center gap-2 text-sm">
                <input
                    v-model="filters.past"
                    type="checkbox"
                    class="size-4 rounded border-input"
                />
                Show departed
            </label>
        </div>

        <div class="panel overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="px-4 py-3 font-medium">Departure</th>
                        <th class="px-4 py-3 font-medium">Route</th>
                        <th class="px-4 py-3 font-medium">Seats</th>
                        <th class="px-4 py-3 text-right font-medium">
                            Adult fare
                        </th>
                        <th class="px-4 py-3 font-medium">Pay within</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="group in groups.data" :key="group.id">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="font-medium">
                                {{ formatDate(group.departure_date) }}
                            </div>
                            <div class="text-xs text-muted-foreground">
                                {{ formatWeekday(group.departure_date) }}
                                <template v-if="group.duration_days">
                                    · {{ group.duration_days }} days</template
                                >
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ group.sector }}</div>
                            <div class="text-xs text-muted-foreground">
                                {{ group.airline.name }} ·
                                {{ group.group_type }}
                            </div>
                            <span
                                class="mt-1 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                :class="
                                    group.sourcing === 'direct'
                                        ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300'
                                        : 'bg-sky-50 text-sky-800 dark:bg-sky-950/50 dark:text-sky-300'
                                "
                            >
                                <component
                                    :is="
                                        group.sourcing === 'direct'
                                            ? Building
                                            : Handshake
                                    "
                                    class="size-3"
                                />
                                {{
                                    group.sourcing === 'direct'
                                        ? 'Direct'
                                        : (group.supplier ?? 'Outsourced')
                                }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="font-medium tabular-nums">
                                {{ group.seats_available }} left of
                                {{ group.total_seats }}
                            </div>
                            <div class="text-xs text-muted-foreground">
                                {{ group.seats_confirmed }} confirmed ·
                                {{ group.seats_held }} on hold
                            </div>
                        </td>
                        <td
                            class="px-4 py-3 text-right whitespace-nowrap tabular-nums"
                        >
                            {{ formatPKR(group.adult_fare) }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <button
                                type="button"
                                class="group/window inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold tabular-nums transition hover:border-gold hover:bg-gold/10"
                                :title="`Change how long agencies have to pay for ${group.sector}`"
                                @click="windowFor = group"
                            >
                                <Clock class="size-3.5 text-gold-dark" />
                                {{ group.hold_minutes }} min
                                <Pencil
                                    class="size-3 text-muted-foreground opacity-60 group-hover/window:opacity-100"
                                />
                            </button>
                        </td>
                        <td class="px-4 py-3">
                            <StatusBadge :status="group.status" />
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1.5">
                                <Button
                                    v-if="group.status !== 'published'"
                                    size="sm"
                                    @click="setStatus(group, 'published')"
                                    ><Send /> Publish</Button
                                >
                                <Button
                                    v-else
                                    size="sm"
                                    variant="outline"
                                    class="text-destructive hover:text-destructive"
                                    @click="closing = group"
                                    ><Ban /> Close</Button
                                >
                                <Button size="sm" variant="outline" as-child>
                                    <Link
                                        :href="
                                            admin.bookings.index({
                                                query: { group: group.id },
                                            })
                                        "
                                        ><Ticket /> Bookings</Link
                                    >
                                </Button>
                                <Button size="sm" variant="ghost" as-child>
                                    <Link
                                        :href="admin.groups.edit(group.id)"
                                        aria-label="Edit group"
                                        ><Pencil /> Edit</Link
                                    >
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="groups.data.length === 0">
                        <td
                            colspan="7"
                            class="px-4 py-10 text-center text-muted-foreground"
                        >
                            No groups match these filters.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pagination
            :links="groups.links"
            :from="groups.from"
            :to="groups.to"
            :total="groups.total"
        />

        <PaymentWindowDialog
            v-model:open="windowOpen"
            :group="windowFor"
            :default-minutes="defaultHoldMinutes"
        />

        <ConfirmDialog
            v-model:open="closeOpen"
            :title="`Close ${closing?.sector ?? ''} sales?`"
            description="Agencies can no longer book this group. Existing bookings stay as they are; you can publish it again later."
            confirm-label="Close sales"
            destructive
            @confirm="closing && setStatus(closing, 'closed')"
        />
    </div>
</template>
