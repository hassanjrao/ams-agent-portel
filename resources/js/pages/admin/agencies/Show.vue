<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Ban, CheckCircle2, Landmark } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { balanceLabel, formatDate, formatPKR } from '@/lib/format';
import admin from '@/routes/admin';

type Agency = {
    id: number;
    name: string;
    contact_person: string;
    phone: string;
    email: string;
    city: string;
    country: string;
    address: string | null;
    admin_notes: string | null;
    status: 'pending' | 'active' | 'suspended';
    approved_at: string | null;
    created_at: string;
    balance: number;
    users: { id: number; name: string; email: string; phone: string | null }[];
};

type BookingRow = {
    id: number;
    reference: string;
    sector: string;
    departure_date: string;
    passengers: number;
    total_amount: number;
    status: string;
};

const props = defineProps<{ agency: Agency; recentBookings: BookingRow[] }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Agencies', href: admin.agencies.index() }],
    },
});

const form = useForm({
    status: '',
    admin_notes: props.agency.admin_notes ?? '',
});

function setStatus(status: 'active' | 'suspended') {
    form.status = status;
    form.submit(admin.agencies.status(props.agency.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="agency.name" />

    <div class="flex w-full max-w-5xl flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <div class="flex items-center gap-3">
                    <h1
                        class="font-display text-2xl font-semibold sm:text-[1.7rem]"
                    >
                        {{ agency.name }}
                    </h1>
                    <StatusBadge :status="agency.status" />
                </div>
                <p class="text-sm text-muted-foreground">
                    Signed up {{ formatDate(agency.created_at) }}
                    <template v-if="agency.approved_at">
                        · approved
                        {{ formatDate(agency.approved_at) }}</template
                    >
                </p>
            </div>
            <div class="flex gap-2">
                <Button
                    v-if="agency.status !== 'active'"
                    :disabled="form.processing"
                    @click="setStatus('active')"
                    ><CheckCircle2 />
                    {{ agency.status === 'pending' ? 'Approve' : 'Reactivate' }}
                </Button>
                <Button
                    v-if="agency.status !== 'suspended'"
                    variant="outline"
                    :disabled="form.processing"
                    @click="setStatus('suspended')"
                    ><Ban /> Suspend</Button
                >
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="panel p-5 md:col-span-2">
                <h2 class="mb-3 font-medium">Details</h2>
                <dl class="grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-muted-foreground">Contact person</dt>
                        <dd>{{ agency.contact_person }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Phone</dt>
                        <dd>
                            <a
                                :href="`tel:${agency.phone}`"
                                class="hover:underline"
                                >{{ agency.phone }}</a
                            >
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Email</dt>
                        <dd>{{ agency.email }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">City</dt>
                        <dd>{{ agency.city }}, {{ agency.country }}</dd>
                    </div>
                    <div v-if="agency.address" class="sm:col-span-2">
                        <dt class="text-muted-foreground">Address</dt>
                        <dd>{{ agency.address }}</dd>
                    </div>
                </dl>
            </div>
            <div
                class="panel flex flex-col border-gold/40 bg-gradient-to-br from-card to-sand/60 p-5 dark:to-secondary"
            >
                <h2 class="text-sm text-muted-foreground">
                    {{ balanceLabel(agency.balance) }}
                </h2>
                <p class="mt-2 text-2xl font-extrabold tabular-nums">
                    {{ formatPKR(Math.abs(agency.balance)) }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    Confirmed bookings minus approved payments and refunds.
                </p>
                <Button as-child variant="outline" size="sm" class="mt-4 w-fit">
                    <Link :href="admin.agencies.ledger(agency.id)"
                        ><Landmark /> Statement &amp; adjustments</Link
                    >
                </Button>
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="admin_notes"
                >Internal notes (saved with Approve / Suspend)</Label
            >
            <textarea
                id="admin_notes"
                v-model="form.admin_notes"
                rows="2"
                class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
            ></textarea>
            <InputError :message="form.errors.admin_notes" />
        </div>

        <div class="panel overflow-hidden">
            <h2 class="border-b px-4 py-3 font-medium">Staff logins</h2>
            <ul class="divide-y text-sm">
                <li
                    v-for="user in agency.users"
                    :key="user.id"
                    class="flex flex-wrap justify-between gap-2 px-4 py-3"
                >
                    <span class="font-medium">{{ user.name }}</span>
                    <span class="text-muted-foreground"
                        >{{ user.email }}
                        <template v-if="user.phone">
                            · {{ user.phone }}</template
                        ></span
                    >
                </li>
            </ul>
        </div>

        <div class="panel overflow-x-auto">
            <div class="flex items-center justify-between border-b px-4 py-3">
                <h2 class="font-semibold">Recent bookings</h2>
                <Link
                    :href="
                        admin.bookings.index({ query: { search: agency.name } })
                    "
                    class="text-sm font-semibold text-highlight hover:underline"
                    >All bookings</Link
                >
            </div>
            <table class="data-table">
                <tbody class="divide-y">
                    <tr v-for="booking in recentBookings" :key="booking.id">
                        <td class="px-4 py-3 font-mono text-xs">
                            <Link
                                :href="admin.bookings.show(booking.id)"
                                class="font-bold hover:text-highlight"
                                >{{ booking.reference }}</Link
                            >
                        </td>
                        <td class="px-4 py-3">
                            {{ booking.sector }} ·
                            {{ formatDate(booking.departure_date) }}
                        </td>
                        <td class="px-4 py-3 tabular-nums">
                            {{ booking.passengers }} pax
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {{ formatPKR(booking.total_amount) }}
                        </td>
                        <td class="px-4 py-3">
                            <StatusBadge :status="booking.status" />
                        </td>
                    </tr>
                    <tr v-if="recentBookings.length === 0">
                        <td
                            colspan="5"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No bookings yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
