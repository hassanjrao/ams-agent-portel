<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { PlaneTakeoff, Search, X } from '@lucide/vue';
import { computed, reactive, watch } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import GroupCard from '@/components/GroupCard.vue';
import NativeSelect from '@/components/NativeSelect.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import groups from '@/routes/groups';
import type {
    AirlineOption,
    GroupCard as Group,
    Paginated,
} from '@/types/portal';

const props = defineProps<{
    groups: Paginated<Group>;
    filters: {
        type?: string;
        airline?: string;
        month?: string;
        search?: string;
    };
    groupTypes: { id: number; name: string; open_count: number }[];
    airlines: AirlineOption[];
    months: string[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Find groups', href: groups.index() }],
    },
});

const filters = reactive({
    type: props.filters.type ?? '',
    airline: props.filters.airline ?? '',
    month: props.filters.month ?? '',
    search: props.filters.search ?? '',
});

const total = computed(() =>
    props.groupTypes.reduce((sum, t) => sum + t.open_count, 0),
);
const filtered = computed(
    () =>
        !!(filters.type || filters.airline || filters.month || filters.search),
);

function monthLabel(month: string): string {
    return new Date(`${month}-01T00:00:00`).toLocaleDateString('en-GB', {
        month: 'long',
        year: 'numeric',
    });
}

let timer: ReturnType<typeof setTimeout> | undefined;
watch(filters, () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get(
            groups.index.url(),
            {
                type: filters.type || undefined,
                airline: filters.airline || undefined,
                month: filters.month || undefined,
                search: filters.search || undefined,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }, 300);
});

function clear() {
    Object.assign(filters, { type: '', airline: '', month: '', search: '' });
}
</script>

<template>
    <Head title="Find groups" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            eyebrow="Seats on sale"
            title="Find groups"
            :description="`${total} group${total === 1 ? '' : 's'} on sale, soonest departures first.`"
        />

        <!-- Category tabs, each with how many groups it has on sale. -->
        <div class="-mx-1 flex gap-2 overflow-x-auto px-1 pb-1">
            <button
                type="button"
                class="flex shrink-0 items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold transition"
                :class="
                    !filters.type
                        ? 'border-ink bg-ink text-cream dark:border-gold dark:bg-gold dark:text-night'
                        : 'bg-card hover:border-gold/50'
                "
                @click="filters.type = ''"
            >
                All
                <span class="text-xs opacity-70">{{ total }}</span>
            </button>
            <button
                v-for="type in groupTypes"
                :key="type.id"
                type="button"
                class="flex shrink-0 items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold transition"
                :class="
                    filters.type === String(type.id)
                        ? 'border-ink bg-ink text-cream dark:border-gold dark:bg-gold dark:text-night'
                        : 'bg-card hover:border-gold/50'
                "
                @click="filters.type = String(type.id)"
            >
                {{ type.name }}
                <span class="text-xs opacity-70">{{ type.open_count }}</span>
            </button>
        </div>

        <div class="panel flex flex-wrap items-center gap-2 p-3">
            <div class="relative w-full sm:w-64">
                <Search
                    class="absolute top-2.5 left-2.5 size-4 text-muted-foreground"
                />
                <Input
                    v-model="filters.search"
                    placeholder="City code or flight, e.g. JED, SV801"
                    class="pl-8"
                />
            </div>
            <NativeSelect v-model="filters.airline" class="w-full sm:w-48">
                <option value="">All airlines</option>
                <option
                    v-for="airline in airlines"
                    :key="airline.id"
                    :value="String(airline.id)"
                >
                    {{ airline.name }}
                </option>
            </NativeSelect>
            <NativeSelect v-model="filters.month" class="w-full sm:w-44">
                <option value="">Any month</option>
                <option v-for="month in months" :key="month" :value="month">
                    {{ monthLabel(month) }}
                </option>
            </NativeSelect>
            <Button v-if="filtered" variant="ghost" size="sm" @click="clear"
                ><X /> Clear</Button
            >
        </div>

        <div class="grid gap-4 2xl:grid-cols-2">
            <GroupCard
                v-for="group in props.groups.data"
                :key="group.id"
                :group="group"
            />
        </div>

        <div v-if="props.groups.data.length === 0" class="panel">
            <EmptyState
                :icon="PlaneTakeoff"
                :title="
                    filtered
                        ? 'No groups match these filters'
                        : 'No groups on sale right now'
                "
                :description="
                    filtered
                        ? 'Try another month or airline, or clear the filters.'
                        : 'New seat blocks appear here as soon as AMS publishes them.'
                "
            >
                <Button v-if="filtered" variant="outline" @click="clear"
                    >Clear filters</Button
                >
            </EmptyState>
        </div>

        <Pagination
            :links="props.groups.links"
            :from="props.groups.from"
            :to="props.groups.to"
            :total="props.groups.total"
        />
    </div>
</template>
