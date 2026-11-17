<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Pencil, Save, UserRound } from '@lucide/vue';
import { computed, ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import PassengerEditor from '@/components/PassengerEditor.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { formatDate, titleCase } from '@/lib/format';
import { passengersPayload, syncPassengers } from '@/lib/passengers';
import type { BookingDetail, PassengerDraft } from '@/types/portal';
import type { RouteDefinition } from '@/wayfinder';

// The passenger list, readable by default and editable when allowed. AMS staff also edit the
// booking's PNR and each passenger's ticket number here.
const props = defineProps<{
    booking: BookingDetail;
    action: RouteDefinition<'put'>;
    staff?: boolean;
}>();

const editing = ref(false);

function startingList(): PassengerDraft[] {
    // A booking held without names gets empty rows to fill in.
    return syncPassengers(
        props.booking.passengers.map((p) => ({
            type: p.type,
            title: p.title,
            given_name: p.given_name,
            surname: p.surname,
            passport_number: p.passport_number ?? '',
            nationality: p.nationality ?? '',
            date_of_birth: p.date_of_birth ?? '',
            passport_expiry: p.passport_expiry ?? '',
            ticket_number: p.ticket_number ?? '',
        })),
        {
            adult: props.booking.adults,
            child: props.booking.children,
            infant: props.booking.infants,
        },
    );
}

const form = useForm<{ passengers: PassengerDraft[]; pnr: string }>({
    passengers: startingList(),
    pnr: props.booking.pnr ?? '',
});

function edit() {
    form.defaults({ passengers: startingList(), pnr: props.booking.pnr ?? '' });
    form.reset();
    form.clearErrors();
    editing.value = true;
}

function save() {
    form.transform((data) => ({
        passengers: passengersPayload(data.passengers, props.staff),
        ...(props.staff ? { pnr: data.pnr.trim() || null } : {}),
    })).submit(props.action, {
        preserveScroll: true,
        onSuccess: () => (editing.value = false),
    });
}

const errors = computed(() => form.errors as Partial<Record<string, string>>);
</script>

<template>
    <section class="panel">
        <div
            class="flex flex-wrap items-center justify-between gap-3 border-b px-5 py-4"
        >
            <div>
                <h2 class="font-semibold">Passengers</h2>
                <p class="text-xs text-muted-foreground">
                    {{
                        staff
                            ? 'Names, PNR and ticket numbers.'
                            : booking.can.edit_passengers
                              ? 'You can correct names until the hold ends.'
                              : 'Names are locked. Contact AMS to change one.'
                    }}
                </p>
            </div>
            <Button
                v-if="booking.can.edit_passengers && !editing"
                variant="outline"
                size="sm"
                @click="edit"
                ><Pencil />
                {{ staff ? 'Edit names & tickets' : 'Edit names' }}</Button
            >
        </div>

        <form
            v-if="editing"
            class="flex flex-col gap-4 p-5"
            @submit.prevent="save"
        >
            <div v-if="staff" class="grid max-w-xs gap-1.5">
                <Label for="pnr">Airline PNR</Label>
                <Input
                    id="pnr"
                    v-model="form.pnr"
                    class="font-mono uppercase"
                />
                <InputError :message="form.errors.pnr" />
            </div>
            <PassengerEditor
                v-model="form.passengers"
                :errors="errors"
                :with-tickets="staff"
            />
            <InputError :message="errors.passengers" />
            <div class="flex justify-end gap-2">
                <Button type="button" variant="outline" @click="editing = false"
                    >Cancel</Button
                >
                <Button type="submit" :disabled="form.processing">
                    <Spinner v-if="form.processing" />
                    <Save v-else />
                    Save
                </Button>
            </div>
        </form>

        <template v-else>
            <div v-if="booking.passengers.length" class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Passport</th>
                            <th>Date of birth</th>
                            <th>Ticket</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(p, i) in booking.passengers" :key="p.id">
                            <td class="text-muted-foreground">{{ i + 1 }}</td>
                            <td class="font-semibold whitespace-nowrap">
                                {{ p.title }} {{ p.given_name }}
                                {{ p.surname }}
                            </td>
                            <td>{{ titleCase(p.type) }}</td>
                            <td class="font-mono text-xs whitespace-nowrap">
                                {{ p.passport_number ?? '—' }}
                                <span
                                    v-if="p.passport_expiry"
                                    class="block font-sans text-muted-foreground"
                                    >exp.
                                    {{ formatDate(p.passport_expiry) }}</span
                                >
                            </td>
                            <td class="whitespace-nowrap">
                                {{
                                    p.date_of_birth
                                        ? formatDate(p.date_of_birth)
                                        : '—'
                                }}
                            </td>
                            <td class="font-mono text-xs whitespace-nowrap">
                                {{ p.ticket_number ?? '—' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState
                v-else
                :icon="UserRound"
                title="No names yet"
                description="Add passenger names before the booking is confirmed."
            />
        </template>
    </section>
</template>
