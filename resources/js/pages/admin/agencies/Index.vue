<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Ban,
    Building2,
    CheckCircle2,
    Eye,
    Landmark,
    Lock,
    Search,
} from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import NativeSelect from '@/components/NativeSelect.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
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
    status: 'pending' | 'active' | 'suspended';
    over_free_plan: boolean;
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

// Approve / reactivate / suspend straight from the list, after a confirmation.
const target = ref<{
    agency: AgencyRow;
    status: 'active' | 'suspended';
} | null>(null);
const processing = ref(false);
const error = ref<string>();
const open = computed({
    get: () => target.value !== null,
    set: (value) => {
        if (!value) {
            target.value = null;
        }
    },
});

function ask(agency: AgencyRow, status: 'active' | 'suspended') {
    error.value = undefined;
    target.value = { agency, status };
}

function apply() {
    if (!target.value) {
        return;
    }
    router.patch(
        admin.agencies.status.url(target.value.agency.id),
        { status: target.value.status },
        {
            preserveScroll: true,
            onStart: () => (processing.value = true),
            onFinish: () => (processing.value = false),
            onSuccess: () => (target.value = null),
            onError: (errors) => (error.value = Object.values(errors)[0]),
        },
    );
}

const dialog = computed(() => {
    const t = target.value;
    if (!t) {
        return { title: '', description: '', label: '' };
    }
    if (t.status === 'suspended') {
        return {
            title: `Suspend ${t.agency.name}?`,
            description:
                'It can no longer hold seats or send payments. Its bookings and statement stay as they are, and it is emailed.',
            label: 'Suspend agency',
        };
    }

    return t.agency.status === 'pending'
        ? {
              title: `Approve ${t.agency.name}?`,
              description:
                  'It can start holding seats and sending payments straight away, and is emailed that it is approved.',
              label: 'Approve agency',
          }
        : {
              title: `Reactivate ${t.agency.name}?`,
              description: 'It can book and pay again, and is emailed.',
              label: 'Reactivate agency',
          };
});
</script>

<template>
    <Head title="Agencies" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            eyebrow="Daily work"
            title="Agencies"
            description="Travel agencies that book your seats. New sign-ups wait here for approval."
        />

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
            <NativeSelect v-model="filters.status" class="w-full sm:w-48">
                <option value="">All statuses</option>
                <option value="pending">Waiting for approval</option>
                <option value="active">Active</option>
                <option value="suspended">Suspended</option>
            </NativeSelect>
        </div>

        <div class="panel overflow-x-auto">
            <TooltipProvider :delay-duration="200">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Agency</th>
                            <th>Contact</th>
                            <th>Bookings</th>
                            <th class="text-right">Balance owed</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="agency in agencies.data" :key="agency.id">
                            <td>
                                <Link
                                    :href="admin.agencies.show(agency.id)"
                                    class="font-semibold hover:text-highlight"
                                    >{{ agency.name }}</Link
                                >
                                <div class="text-xs text-muted-foreground">
                                    {{ agency.city }} · joined
                                    {{ formatDate(agency.created_at) }}
                                </div>
                            </td>
                            <td>
                                <div>{{ agency.contact_person }}</div>
                                <div class="text-xs text-muted-foreground">
                                    {{ agency.phone }} · {{ agency.email }}
                                </div>
                            </td>
                            <td class="tabular-nums">
                                {{ agency.bookings_count }}
                            </td>
                            <td
                                class="text-right whitespace-nowrap tabular-nums"
                            >
                                {{ formatPKR(agency.balance) }}
                            </td>
                            <td>
                                <div
                                    class="flex flex-wrap items-center gap-1.5"
                                >
                                    <StatusBadge :status="agency.status" />
                                    <span
                                        v-if="agency.over_free_plan"
                                        class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap text-red-800 dark:bg-red-950 dark:text-red-300"
                                        title="Signed up after the free plan's limit: can log in but not book or pay"
                                        ><Lock class="size-3" /> Over free
                                        limit</span
                                    >
                                </div>
                            </td>
                            <td>
                                <div
                                    class="flex items-center justify-end gap-1.5"
                                >
                                    <Button
                                        v-if="agency.status !== 'active'"
                                        size="sm"
                                        @click="ask(agency, 'active')"
                                        ><CheckCircle2 />
                                        {{
                                            agency.status === 'pending'
                                                ? 'Approve'
                                                : 'Activate'
                                        }}</Button
                                    >
                                    <Button
                                        v-else
                                        size="sm"
                                        variant="outline"
                                        class="text-destructive hover:text-destructive"
                                        @click="ask(agency, 'suspended')"
                                        ><Ban /> Suspend</Button
                                    >
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        as-child
                                    >
                                        <Link
                                            :href="
                                                admin.agencies.show(agency.id)
                                            "
                                            ><Eye /> View</Link
                                        >
                                    </Button>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                size="icon-sm"
                                                variant="ghost"
                                                as-child
                                            >
                                                <Link
                                                    :href="
                                                        admin.agencies.ledger(
                                                            agency.id,
                                                        )
                                                    "
                                                    aria-label="Statement"
                                                    ><Landmark
                                                /></Link>
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent
                                            >Statement</TooltipContent
                                        >
                                    </Tooltip>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </TooltipProvider>
            <EmptyState
                v-if="agencies.data.length === 0"
                :icon="Building2"
                title="No agencies match these filters"
            />
        </div>

        <Pagination
            :links="agencies.links"
            :from="agencies.from"
            :to="agencies.to"
            :total="agencies.total"
        />

        <ConfirmDialog
            v-model:open="open"
            :title="dialog.title"
            :description="dialog.description"
            :confirm-label="dialog.label"
            :destructive="target?.status === 'suspended'"
            :processing="processing"
            :error="error"
            @confirm="apply"
        />
    </div>
</template>
