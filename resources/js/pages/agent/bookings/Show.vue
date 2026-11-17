<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CircleCheck,
    Printer,
    ReceiptText,
    XCircle,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import BankAccountCard from '@/components/BankAccountCard.vue';
import BookingTicket from '@/components/BookingTicket.vue';
import PassengersPanel from '@/components/PassengersPanel.vue';
import PaymentDialog from '@/components/PaymentDialog.vue';
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
import { formatDate, formatPKR } from '@/lib/format';
import bookings from '@/routes/bookings';
import type { BankAccount, BookingDetail } from '@/types/portal';

const props = defineProps<{
    booking: BookingDetail;
    bankAccounts: BankAccount[];
}>();

defineOptions({
    layout: (props: { booking: BookingDetail }) => ({
        breadcrumbs: [
            { title: 'My bookings', href: bookings.index() },
            {
                title: props.booking.reference,
                href: bookings.show(props.booking.id),
            },
        ],
    }),
});

const paying = ref(false);
const cancelling = ref(false);
const working = ref(false);

const paidSoFar = computed(() =>
    props.booking.payments
        .filter((p) => p.status !== 'rejected')
        .reduce((sum, p) => sum + p.amount, 0),
);
const bookingOption = computed(() => [
    {
        id: props.booking.id,
        label: `${props.booking.reference} · ${props.booking.sector}`,
        total_amount: props.booking.total_amount,
    },
]);

function cancel() {
    router.post(
        bookings.cancel.url(props.booking.id),
        {},
        {
            preserveScroll: true,
            onStart: () => (working.value = true),
            onFinish: () => {
                working.value = false;
                cancelling.value = false;
            },
        },
    );
}

function print() {
    window.print();
}
</script>

<template>
    <Head :title="`Booking ${booking.reference}`" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <div
            class="flex flex-wrap items-center justify-between gap-3 print:hidden"
        >
            <Link
                :href="bookings.index()"
                class="flex items-center gap-1.5 text-sm font-semibold text-muted-foreground hover:text-foreground"
                ><ArrowLeft class="size-4" /> My bookings</Link
            >
            <div class="flex flex-wrap gap-2">
                <Button variant="ghost" @click="print"
                    ><Printer /> Print</Button
                >
                <Button
                    v-if="booking.can.cancel"
                    variant="outline"
                    class="text-destructive hover:text-destructive"
                    @click="cancelling = true"
                    ><XCircle /> Release seats</Button
                >
                <Button v-if="booking.can.pay" @click="paying = true"
                    ><ReceiptText /> Send payment</Button
                >
            </div>
        </div>

        <div class="grid items-start gap-6 xl:grid-cols-[1fr_22rem]">
            <div class="flex flex-col gap-5">
                <BookingTicket :booking="booking" />
                <PassengersPanel
                    :booking="booking"
                    :action="bookings.passengers(booking.id)"
                />
            </div>

            <aside class="flex flex-col gap-5 print:hidden">
                <!-- What to do next, depending on where the booking is. -->
                <section
                    v-if="booking.status === 'on_hold'"
                    class="panel border-amber-300 p-5 dark:border-amber-900"
                >
                    <h2 class="font-semibold">Pay to confirm</h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Transfer
                        <strong class="text-foreground">{{
                            formatPKR(booking.total_amount)
                        }}</strong>
                        to one of these accounts and send the receipt before the
                        hold runs out. AMS confirms the seats once the money is
                        in.
                    </p>
                    <div class="mt-4 flex flex-col gap-3">
                        <BankAccountCard
                            v-for="account in bankAccounts"
                            :key="account.id"
                            :account="account"
                        />
                    </div>
                    <Button class="mt-4 w-full" @click="paying = true"
                        ><ReceiptText /> I have paid: send receipt</Button
                    >
                </section>
                <section
                    v-else-if="booking.status === 'confirmed'"
                    class="panel flex gap-3 border-emerald-300 p-5 dark:border-emerald-900"
                >
                    <CircleCheck
                        class="size-5 shrink-0 text-emerald-600 dark:text-emerald-400"
                    />
                    <div>
                        <h2 class="font-semibold">Confirmed</h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            These seats are yours and the booking is on your
                            statement. Ticket numbers appear above once issued.
                        </p>
                    </div>
                </section>
                <section v-else class="panel p-5">
                    <h2 class="font-semibold">
                        {{
                            booking.status === 'expired'
                                ? 'The hold ran out'
                                : 'Cancelled'
                        }}
                    </h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{
                            booking.status === 'expired'
                                ? 'The seats went back on sale. If you already paid, send the receipt: AMS can still confirm the booking while seats are free.'
                                : 'These seats were released.'
                        }}
                    </p>
                </section>

                <section class="panel overflow-hidden">
                    <div
                        class="flex items-center justify-between border-b px-5 py-4"
                    >
                        <h2 class="font-semibold">Payments</h2>
                        <span class="text-xs text-muted-foreground"
                            >{{ formatPKR(paidSoFar) }} sent</span
                        >
                    </div>
                    <ul class="divide-y">
                        <li
                            v-for="payment in booking.payments"
                            :key="payment.id"
                            class="flex items-center justify-between gap-3 px-5 py-3 text-sm"
                        >
                            <div>
                                <p class="font-semibold tabular-nums">
                                    {{ formatPKR(payment.amount) }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ payment.bank }} ·
                                    {{ formatDate(payment.paid_on) }}
                                </p>
                            </div>
                            <StatusBadge :status="payment.status" />
                        </li>
                    </ul>
                    <p
                        v-if="booking.payments.length === 0"
                        class="px-5 py-4 text-sm text-muted-foreground"
                    >
                        No payment sent for this booking yet.
                    </p>
                </section>
            </aside>
        </div>

        <PaymentDialog
            v-model:open="paying"
            :bank-accounts="bankAccounts"
            :bookings="bookingOption"
            :booking-id="booking.id"
        />

        <Dialog v-model:open="cancelling">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Release these seats?</DialogTitle>
                    <DialogDescription
                        >Booking {{ booking.reference }} will be cancelled and
                        its {{ booking.seats }} seats go back on sale. This
                        can't be undone.</DialogDescription
                    >
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <Button variant="outline" @click="cancelling = false"
                        >Keep booking</Button
                    >
                    <Button
                        variant="destructive"
                        :disabled="working"
                        @click="cancel"
                        >Release seats</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
