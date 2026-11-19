<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Building2,
    CircleCheck,
    ExternalLink,
    Mail,
    Phone,
    XCircle,
} from '@lucide/vue';
import { ref } from 'vue';
import BookingPdfMenu from '@/components/BookingPdfMenu.vue';
import BookingTicket from '@/components/BookingTicket.vue';
import InputError from '@/components/InputError.vue';
import PassengersPanel from '@/components/PassengersPanel.vue';
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
import { balanceLabel, formatDate, formatPKR } from '@/lib/format';
import admin from '@/routes/admin';
import type { BookingDetail } from '@/types/portal';

type AdminBooking = BookingDetail & {
    agency_balance: number;
    supplier_pnr: string | null;
};

const props = defineProps<{ booking: AdminBooking }>();

defineOptions({
    layout: (props: { booking: AdminBooking }) => ({
        breadcrumbs: [
            { title: 'Bookings', href: admin.bookings.index() },
            {
                title: props.booking.reference,
                href: admin.bookings.show(props.booking.id),
            },
        ],
    }),
});

const confirming = ref(false);
const cancelling = ref(false);

const confirmForm = useForm({
    pnr: props.booking.pnr ?? props.booking.supplier_pnr ?? '',
});
const cancelForm = useForm({ reason: '' });

function confirm() {
    confirmForm.submit(admin.bookings.confirm(props.booking.id), {
        preserveScroll: true,
        onSuccess: () => (confirming.value = false),
    });
}

function cancel() {
    cancelForm.submit(admin.bookings.cancel(props.booking.id), {
        preserveScroll: true,
        onSuccess: () => (cancelling.value = false),
    });
}
</script>

<template>
    <Head :title="`Booking ${booking.reference}`" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <Link
                :href="admin.bookings.index()"
                class="flex items-center gap-1.5 text-sm font-semibold text-muted-foreground hover:text-foreground"
                ><ArrowLeft class="size-4" /> Bookings</Link
            >
            <div class="flex flex-wrap gap-2">
                <BookingPdfMenu :booking-id="booking.id" />
                <Button
                    v-if="booking.can.cancel"
                    variant="outline"
                    class="text-destructive hover:text-destructive"
                    @click="cancelling = true"
                    ><XCircle /> Cancel booking</Button
                >
                <Button v-if="booking.can.confirm" @click="confirming = true"
                    ><CircleCheck /> Confirm &amp; charge</Button
                >
            </div>
        </div>

        <div class="grid items-start gap-6 xl:grid-cols-[1fr_21rem]">
            <div class="flex flex-col gap-5">
                <BookingTicket :booking="booking" />
                <PassengersPanel
                    :booking="booking"
                    :action="admin.bookings.passengers(booking.id)"
                    staff
                />
            </div>

            <aside class="flex flex-col gap-5">
                <section class="panel p-5">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p
                                class="text-[11px] font-bold tracking-[0.18em] text-highlight uppercase"
                            >
                                Agency
                            </p>
                            <Link
                                :href="admin.agencies.show(booking.agency.id)"
                                class="text-lg font-bold hover:text-highlight"
                                >{{ booking.agency.name }}</Link
                            >
                            <p class="text-sm text-muted-foreground">
                                {{ booking.agency.contact_person }} ·
                                {{ booking.agency.city }}
                            </p>
                        </div>
                        <Building2 class="size-5 text-gold" />
                    </div>
                    <div class="mt-3 flex flex-col gap-1.5 text-sm">
                        <a
                            :href="`tel:${booking.agency.phone}`"
                            class="flex items-center gap-2 hover:text-highlight"
                            ><Phone class="size-4 text-muted-foreground" />
                            {{ booking.agency.phone }}</a
                        >
                        <a
                            :href="`mailto:${booking.agency.email}`"
                            class="flex items-center gap-2 hover:text-highlight"
                            ><Mail class="size-4 text-muted-foreground" />
                            {{ booking.agency.email }}</a
                        >
                    </div>
                    <div
                        class="mt-4 flex items-center justify-between rounded-lg bg-muted/60 px-3 py-2.5 text-sm"
                    >
                        <span class="text-muted-foreground">{{
                            balanceLabel(booking.agency_balance)
                        }}</span>
                        <Link
                            :href="admin.agencies.ledger(booking.agency.id)"
                            class="font-bold tabular-nums hover:text-highlight"
                            >{{
                                formatPKR(Math.abs(booking.agency_balance))
                            }}</Link
                        >
                    </div>
                </section>

                <section class="panel overflow-hidden">
                    <div class="border-b px-5 py-4">
                        <h2 class="font-semibold">Payments for this booking</h2>
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
                                <a
                                    :href="payment.receipt_url"
                                    target="_blank"
                                    rel="noopener"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-highlight hover:underline"
                                    >Receipt <ExternalLink class="size-3"
                                /></a>
                            </div>
                            <StatusBadge :status="payment.status" />
                        </li>
                    </ul>
                    <p
                        v-if="booking.payments.length === 0"
                        class="px-5 py-4 text-sm text-muted-foreground"
                    >
                        No payment linked to this booking. The agency may have
                        paid to its account instead: check its statement.
                    </p>
                    <div class="border-t px-5 py-3">
                        <Link
                            :href="admin.payments.index()"
                            class="text-sm font-semibold text-highlight hover:underline"
                            >Payments to check</Link
                        >
                    </div>
                </section>

                <section
                    v-if="booking.supplier_pnr"
                    class="panel px-5 py-4 text-sm"
                >
                    <span class="text-muted-foreground"
                        >Group's airline PNR</span
                    >
                    <p class="font-mono font-bold">
                        {{ booking.supplier_pnr }}
                    </p>
                </section>
            </aside>
        </div>

        <Dialog v-model:open="confirming">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Confirm {{ booking.reference }}?</DialogTitle>
                    <DialogDescription
                        >{{ formatPKR(booking.total_amount) }} is charged to
                        {{ booking.agency.name }}'s statement and the agency is
                        emailed. Do this once the money is in or the agency has
                        credit.</DialogDescription
                    >
                </DialogHeader>
                <form class="grid gap-1.5" @submit.prevent="confirm">
                    <Label for="confirm-pnr">PNR (optional)</Label>
                    <Input
                        id="confirm-pnr"
                        v-model="confirmForm.pnr"
                        class="font-mono uppercase"
                    />
                    <InputError
                        :message="
                            confirmForm.errors.pnr ||
                            (confirmForm.errors as Record<string, string>)
                                .status
                        "
                    />
                </form>
                <DialogFooter class="gap-2">
                    <Button variant="outline" @click="confirming = false"
                        >Not yet</Button
                    >
                    <Button :disabled="confirmForm.processing" @click="confirm">
                        <Spinner v-if="confirmForm.processing" />
                        <CircleCheck v-else />
                        Confirm &amp; charge
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="cancelling">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Cancel {{ booking.reference }}?</DialogTitle>
                    <DialogDescription>
                        The seats go back on sale and the agency is emailed the
                        reason.
                        <template v-if="booking.status === 'confirmed'"
                            >{{ formatPKR(booking.total_amount) }} is credited
                            back to its statement; post any airline penalty as
                            an adjustment.</template
                        >
                    </DialogDescription>
                </DialogHeader>
                <form class="grid gap-1.5" @submit.prevent="cancel">
                    <Label for="reason">Reason</Label>
                    <Input
                        id="reason"
                        v-model="cancelForm.reason"
                        placeholder="e.g. Not paid, agency asked to cancel"
                    />
                    <InputError
                        :message="
                            cancelForm.errors.reason ||
                            (cancelForm.errors as Record<string, string>).status
                        "
                    />
                </form>
                <DialogFooter class="gap-2">
                    <Button variant="outline" @click="cancelling = false"
                        >Keep it</Button
                    >
                    <Button
                        variant="destructive"
                        :disabled="cancelForm.processing"
                        @click="cancel"
                        >Cancel booking</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
