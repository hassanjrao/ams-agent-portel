<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Search } from '@lucide/vue';
import { reactive, watch } from 'vue';
import NativeSelect from '@/components/NativeSelect.vue';
import Pagination from '@/components/Pagination.vue';
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
    status: 'draft' | 'published' | 'closed';
};

const props = defineProps<{
    groups: Paginated<GroupRow>;
    filters: {
        type?: string;
        status?: string;
        search?: string;
        past?: string;
    };
    groupTypes: Option[];
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
});

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
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }, 300);
});

function setStatus(group: GroupRow, status: GroupRow['status']) {
    router.patch(
        admin.groups.status.url(group.id),
        { status },
        { preserveScroll: true },
    );
}
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
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3"></th>
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
                        <td class="px-4 py-3">
                            <StatusBadge :status="group.status" />
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-1">
                                <Button
                                    v-if="group.status !== 'published'"
                                    size="sm"
                                    variant="outline"
                                    @click="setStatus(group, 'published')"
                                    >Publish</Button
                                >
                                <Button
                                    v-else
                                    size="sm"
                                    variant="outline"
                                    @click="setStatus(group, 'closed')"
                                    >Close</Button
                                >
                                <Button size="icon-sm" variant="ghost" as-child>
                                    <Link
                                        :href="admin.groups.edit(group.id)"
                                        aria-label="Edit group"
                                        ><Pencil
                                    /></Link>
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="groups.data.length === 0">
                        <td
                            colspan="6"
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
    </div>
</template>
