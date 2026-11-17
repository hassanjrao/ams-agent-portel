<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import { reactive, watch } from 'vue';
import NativeSelect from '@/components/NativeSelect.vue';
import Pagination from '@/components/Pagination.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Input } from '@/components/ui/input';
import { formatDate, formatPKR } from '@/lib/format';
import admin from '@/routes/admin';
import type { Paginated } from '@/types/portal';

type AgencyRow = {
    id: number;
    name: string;
    contact_person: string;
    phone: string;
    email: string;
    city: string;
    bookings_count: number;
    status: string;
    balance: number;
    created_at: string;
};

const props = defineProps<{
    agencies: Paginated<AgencyRow>;
    filters: { status?: string; search?: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Agencies', href: admin.agencies.index() }],
    },
});

const filters = reactive({
    status: props.filters.status ?? '',
    search: props.filters.search ?? '',
});

let timer: ReturnType<typeof setTimeout> | undefined;
watch(filters, () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get(
            admin.agencies.index.url(),
            {
                status: filters.status || undefined,
                search: filters.search || undefined,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }, 300);
});
</script>

<template>
    <Head title="Agencies" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <div>
            <h1 class="font-display text-2xl font-semibold sm:text-[1.7rem]">
                Agencies
            </h1>
            <p class="text-sm text-muted-foreground">
                Travel agencies that book your seats. New sign-ups wait here for
                approval.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <div class="relative w-full sm:w-72">
                <Search
                    class="absolute top-2.5 left-2.5 size-4 text-muted-foreground"
                />
                <Input
                    v-model="filters.search"
                    placeholder="Name, person, phone, city"
                    class="pl-8"
                />
            </div>
            <NativeSelect v-model="filters.status" class="w-full sm:w-44">
                <option value="">All statuses</option>
                <option value="pending">Waiting for approval</option>
                <option value="active">Active</option>
                <option value="suspended">Suspended</option>
            </NativeSelect>
        </div>

        <div class="panel overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="px-4 py-3 font-medium">Agency</th>
                        <th class="px-4 py-3 font-medium">Contact</th>
                        <th class="px-4 py-3 font-medium">Bookings</th>
                        <th class="px-4 py-3 text-right font-medium">
                            Balance owed
                        </th>
                        <th class="px-4 py-3 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="agency in agencies.data"
                        :key="agency.id"
                        class="hover:bg-muted/30"
                    >
                        <td class="px-4 py-3">
                            <Link
                                :href="admin.agencies.show(agency.id)"
                                class="font-medium hover:underline"
                                >{{ agency.name }}</Link
                            >
                            <div class="text-xs text-muted-foreground">
                                {{ agency.city }} · joined
                                {{ formatDate(agency.created_at) }}
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div>{{ agency.contact_person }}</div>
                            <div class="text-xs text-muted-foreground">
                                {{ agency.phone }} · {{ agency.email }}
                            </div>
                        </td>
                        <td class="px-4 py-3 tabular-nums">
                            {{ agency.bookings_count }}
                        </td>
                        <td
                            class="px-4 py-3 text-right whitespace-nowrap tabular-nums"
                        >
                            {{ formatPKR(agency.balance) }}
                        </td>
                        <td class="px-4 py-3">
                            <StatusBadge :status="agency.status" />
                        </td>
                    </tr>
                    <tr v-if="agencies.data.length === 0">
                        <td
                            colspan="5"
                            class="px-4 py-10 text-center text-muted-foreground"
                        >
                            No agencies match these filters.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pagination
            :links="agencies.links"
            :from="agencies.from"
            :to="agencies.to"
            :total="agencies.total"
        />
    </div>
</template>
