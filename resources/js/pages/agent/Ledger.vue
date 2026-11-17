<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import LedgerStatement from '@/components/LedgerStatement.vue';
import PageHeader from '@/components/PageHeader.vue';
import { ledger } from '@/routes';
import bookings from '@/routes/bookings';
import type { Statement } from '@/types/portal';

defineProps<{
    statement: Statement;
    filters: { from: string; to: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Statement', href: ledger() }],
    },
});

const agency = usePage().props.myAgency;
</script>

<template>
    <Head title="Statement" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            :eyebrow="agency?.name"
            title="Account statement"
            description="Every charge and payment between your agency and AMS, with the running balance."
        />
        <LedgerStatement
            :statement="statement"
            :filters="filters"
            :url="ledger.url()"
            :booking-href="(id: number) => bookings.show(id)"
        />
    </div>
</template>
