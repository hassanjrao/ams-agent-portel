<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { FileUp, Upload } from '@lucide/vue';
import { computed, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import NativeSelect from '@/components/NativeSelect.vue';
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
import payments from '@/routes/payments';
import type { BankAccount } from '@/types/portal';

type BookingOption = {
    id: number;
    label: string;
    total_amount: number;
};

// Report a bank transfer to AMS with its receipt. Nothing is credited until AMS approves it.
const open = defineModel<boolean>('open', { required: true });

const props = defineProps<{
    bankAccounts: BankAccount[];
    bookings: BookingOption[];
    // Start on a booking, with its total as the amount (from "Send payment" on a booking).
    bookingId?: number | null;
}>();

const today = new Date().toISOString().slice(0, 10);

const form = useForm<{
    bank_account_id: number | string;
    amount: number | string;
    paid_on: string;
    booking_id: number | string;
    description: string;
    receipt: File | null;
}>({
    bank_account_id: props.bankAccounts[0]?.id ?? '',
    amount: '',
    paid_on: today,
    booking_id: '',
    description: '',
    receipt: null,
});

watch(open, (isOpen) => {
    if (!isOpen) {
        return;
    }
    form.reset();
    form.clearErrors();
    const booking = props.bookings.find((b) => b.id === props.bookingId);
    if (booking) {
        form.booking_id = booking.id;
        form.amount = booking.total_amount;
    }
});

// Picking a booking fills in its total, unless an amount was already typed.
watch(
    () => form.booking_id,
    (id) => {
        const booking = props.bookings.find((b) => b.id === Number(id));
        if (booking && !form.amount) {
            form.amount = booking.total_amount;
        }
    },
);

const fileName = computed(() => form.receipt?.name ?? null);

function pick(event: Event) {
    form.receipt = (event.target as HTMLInputElement).files?.[0] ?? null;
}

function submit() {
    form.transform((data) => ({
        ...data,
        booking_id: data.booking_id || null,
    })).submit(payments.store(), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle class="font-display text-xl"
                    >Send a payment</DialogTitle
                >
                <DialogDescription
                    >Tell AMS about a transfer you made and attach the receipt.
                    It is credited to your account once AMS has checked
                    it.</DialogDescription
                >
            </DialogHeader>

            <form class="grid gap-4" @submit.prevent="submit">
                <div class="grid gap-1.5">
                    <Label for="bank">Paid into</Label>
                    <NativeSelect id="bank" v-model="form.bank_account_id">
                        <option
                            v-for="account in bankAccounts"
                            :key="account.id"
                            :value="account.id"
                        >
                            {{ account.bank_name }} ·
                            {{ account.account_number }}
                        </option>
                    </NativeSelect>
                    <InputError :message="form.errors.bank_account_id" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="booking"
                        >For booking
                        <span class="font-normal text-muted-foreground"
                            >(optional)</span
                        ></Label
                    >
                    <NativeSelect id="booking" v-model="form.booking_id">
                        <option value="">General payment to my account</option>
                        <option
                            v-for="booking in bookings"
                            :key="booking.id"
                            :value="booking.id"
                        >
                            {{ booking.label }}
                        </option>
                    </NativeSelect>
                    <InputError :message="form.errors.booking_id" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-1.5">
                        <Label for="amount">Amount (PKR)</Label>
                        <Input
                            id="amount"
                            v-model="form.amount"
                            type="number"
                            min="1"
                            inputmode="numeric"
                            required
                        />
                        <InputError :message="form.errors.amount" />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="paid_on">Date paid</Label>
                        <Input
                            id="paid_on"
                            v-model="form.paid_on"
                            type="date"
                            :max="today"
                            required
                        />
                        <InputError :message="form.errors.paid_on" />
                    </div>
                </div>

                <div class="grid gap-1.5">
                    <Label for="description"
                        >Reference or note
                        <span class="font-normal text-muted-foreground"
                            >(optional)</span
                        ></Label
                    >
                    <Input
                        id="description"
                        v-model="form.description"
                        placeholder="e.g. IBFT ref 123456, cash deposit at Multan branch"
                    />
                    <InputError :message="form.errors.description" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="receipt">Receipt</Label>
                    <label
                        for="receipt"
                        class="flex cursor-pointer items-center gap-3 rounded-xl border border-dashed border-input px-4 py-4 transition hover:border-gold hover:bg-muted/50"
                    >
                        <span
                            class="flex size-10 items-center justify-center rounded-full bg-sand text-gold-dark dark:bg-secondary dark:text-gold-light"
                        >
                            <FileUp class="size-4" />
                        </span>
                        <span class="min-w-0 text-sm">
                            <span class="block truncate font-semibold">{{
                                fileName ?? 'Choose a photo or PDF'
                            }}</span>
                            <span class="text-xs text-muted-foreground"
                                >JPG, PNG or PDF, up to 5 MB</span
                            >
                        </span>
                    </label>
                    <input
                        id="receipt"
                        type="file"
                        accept="image/jpeg,image/png,image/webp,application/pdf"
                        class="sr-only"
                        @change="pick"
                    />
                    <InputError :message="form.errors.receipt" />
                </div>

                <DialogFooter class="gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="open = false"
                        >Cancel</Button
                    >
                    <Button type="submit" :disabled="form.processing">
                        <Spinner v-if="form.processing" />
                        <Upload v-else />
                        Send to AMS
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
