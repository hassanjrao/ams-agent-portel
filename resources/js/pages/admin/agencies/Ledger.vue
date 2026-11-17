<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { SlidersHorizontal } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import LedgerStatement from '@/components/LedgerStatement.vue';
import NativeSelect from '@/components/NativeSelect.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import admin from '@/routes/admin';
import type { Statement } from '@/types/portal';

type Agency = { id: number; name: string; city: string; status: string };

const props = defineProps<{
    agency: Agency;
    statement: Statement;
    filters: { from: string; to: string };
}>();

defineOptions({
    layout: (props: { agency: Agency }) => ({
        breadcrumbs: [
            { title: 'Agencies', href: admin.agencies.index() },
            {
                title: props.agency.name,
                href: admin.agencies.show(props.agency.id),
            },
            {
                title: 'Statement',
                href: admin.agencies.ledger(props.agency.id),
            },
        ],
    }),
});

const adjusting = ref(false);
const form = useForm({
    direction: 'charge' as 'charge' | 'credit',
    amount: '' as number | string,
    description: '',
});

function openAdjust() {
    form.reset();
    form.clearErrors();
    adjusting.value = true;
}

function submit() {
    form.submit(admin.agencies.adjust(props.agency.id), {
        preserveScroll: true,
        onSuccess: () => (adjusting.value = false),
    });
}
</script>

<template>
    <Head :title="`${agency.name} statement`" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <PageHeader
            :eyebrow="agency.city"
            :title="`${agency.name}: statement`"
            description="What this agency owes AMS, entry by entry. Entries are never edited: correct a mistake with an adjustment."
        >
            <template #meta>
                <StatusBadge :status="agency.status" class="mt-2" />
            </template>
            <Button variant="outline" @click="openAdjust"
                ><SlidersHorizontal /> Post adjustment</Button
            >
        </PageHeader>

        <LedgerStatement
            :statement="statement"
            :filters="filters"
            :url="admin.agencies.ledger.url(agency.id)"
            :booking-href="(id: number) => admin.bookings.show(id)"
        />

        <Dialog v-model:open="adjusting">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Post an adjustment</DialogTitle>
                    <DialogDescription
                        >A manual entry on {{ agency.name }}'s statement, e.g.
                        an airline penalty, a service charge or a
                        discount.</DialogDescription
                    >
                </DialogHeader>
                <form class="grid gap-4" @submit.prevent="submit">
                    <div class="grid gap-1.5">
                        <Label for="direction">Type</Label>
                        <NativeSelect id="direction" v-model="form.direction">
                            <option value="charge">
                                Charge the agency (debit)
                            </option>
                            <option value="credit">
                                Credit the agency (credit)
                            </option>
                        </NativeSelect>
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="adj-amount">Amount (PKR)</Label>
                        <Input
                            id="adj-amount"
                            v-model="form.amount"
                            type="number"
                            min="1"
                        />
                        <InputError :message="form.errors.amount" />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="adj-description">Description</Label>
                        <Input
                            id="adj-description"
                            v-model="form.description"
                            placeholder="e.g. Name change penalty AMS-261100042"
                        />
                        <InputError :message="form.errors.description" />
                    </div>
                </form>
                <DialogFooter class="gap-2">
                    <Button variant="outline" @click="adjusting = false"
                        >Cancel</Button
                    >
                    <Button :disabled="form.processing" @click="submit">
                        <Spinner v-if="form.processing" />
                        Post entry
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
