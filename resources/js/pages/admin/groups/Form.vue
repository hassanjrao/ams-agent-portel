<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Plus, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import NativeSelect from '@/components/NativeSelect.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatPKR } from '@/lib/format';
import admin from '@/routes/admin';
import type { AirlineOption, Option } from '@/types/portal';

type Leg = {
    flight_number: string;
    origin: string;
    destination: string;
    departs_at: string;
    arrives_at: string;
    baggage: string;
    meal: boolean;
};

type Group = {
    id: number;
    group_type_id: number;
    airline_id: number;
    sector: string;
    duration_days: number | null;
    total_seats: number;
    adult_fare: number;
    child_fare: number;
    infant_fare: number;
    supplier_pnr: string | null;
    hold_minutes: number | null;
    notes: string | null;
    status: string;
    seats_taken: number;
    legs: Leg[];
};

const props = defineProps<{
    group: Group | null;
    groupTypes: Option[];
    airlines: AirlineOption[];
    statuses: string[];
    defaultHoldMinutes: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Groups', href: admin.groups.index() },
            { title: 'Group', href: admin.groups.create() },
        ],
    },
});

const blankLeg = (): Leg => ({
    flight_number: '',
    origin: '',
    destination: '',
    departs_at: '',
    arrives_at: '',
    baggage: '',
    meal: true,
});

const form = useForm({
    group_type_id: props.group?.group_type_id ?? props.groupTypes[0]?.id ?? '',
    airline_id: props.group?.airline_id ?? '',
    sector: props.group?.sector ?? '',
    duration_days: props.group?.duration_days ?? '',
    total_seats: props.group?.total_seats ?? '',
    adult_fare: props.group?.adult_fare ?? '',
    child_fare: props.group?.child_fare ?? '',
    infant_fare: props.group?.infant_fare ?? '',
    supplier_pnr: props.group?.supplier_pnr ?? '',
    hold_minutes: props.group?.hold_minutes ?? '',
    notes: props.group?.notes ?? '',
    status: props.group?.status ?? 'draft',
    legs: props.group?.legs.map((leg) => ({
        ...leg,
        baggage: leg.baggage ?? '',
    })) ?? [blankLeg(), blankLeg()],
});

const editing = computed(() => props.group !== null);

// Suggest the route from the flights while the admin hasn't typed one.
const suggestedSector = computed(() => {
    const legs = form.legs.filter((leg) => leg.origin && leg.destination);
    if (legs.length === 0) {
        return '';
    }

    const stops = [legs[0].origin];
    for (const leg of legs) {
        if (stops[stops.length - 1] !== leg.origin) {
            stops.push(leg.origin);
        }
        stops.push(leg.destination);
    }

    return stops.join('-').toUpperCase();
});

function addLeg() {
    const last = form.legs[form.legs.length - 1];
    // A new flight usually continues from where the last one landed.
    form.legs.push({ ...blankLeg(), origin: last?.destination ?? '' });
}

function removeLeg(index: number) {
    form.legs.splice(index, 1);
}

function legError(index: number, field: keyof Leg): string | undefined {
    return (form.errors as Record<string, string>)[`legs.${index}.${field}`];
}

function submit() {
    if (!form.sector && suggestedSector.value) {
        form.sector = suggestedSector.value;
    }

    form.submit(
        editing.value
            ? admin.groups.update(props.group!.id)
            : admin.groups.store(),
    );
}
</script>

<template>
    <Head :title="editing ? 'Edit group' : 'New group'" />

    <form
        class="flex w-full max-w-5xl flex-col gap-8 p-4 md:p-6"
        @submit.prevent="submit"
    >
        <div>
            <h1 class="font-display text-2xl font-semibold sm:text-[1.7rem]">
                {{ editing ? `Edit group ${group!.sector}` : 'New group' }}
            </h1>
            <p class="text-sm text-muted-foreground">
                A block of seats on fixed flights. Agencies see it once it is
                published.
            </p>
        </div>

        <section class="panel grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-4">
            <div class="grid gap-2">
                <Label for="group_type_id">Category</Label>
                <NativeSelect id="group_type_id" v-model="form.group_type_id">
                    <option
                        v-for="type in groupTypes"
                        :key="type.id"
                        :value="type.id"
                    >
                        {{ type.name }}
                    </option>
                </NativeSelect>
                <InputError :message="form.errors.group_type_id" />
            </div>
            <div class="grid gap-2">
                <Label for="airline_id">Airline</Label>
                <NativeSelect id="airline_id" v-model="form.airline_id">
                    <option value="" disabled>Choose airline</option>
                    <option
                        v-for="airline in airlines"
                        :key="airline.id"
                        :value="airline.id"
                    >
                        {{ airline.name }} ({{ airline.code }})
                    </option>
                </NativeSelect>
                <InputError :message="form.errors.airline_id" />
            </div>
            <div class="grid gap-2">
                <Label for="sector">Route</Label>
                <Input
                    id="sector"
                    v-model="form.sector"
                    :placeholder="suggestedSector || 'MUX-JED-MUX'"
                    class="uppercase"
                />
                <InputError :message="form.errors.sector" />
            </div>
            <div class="grid gap-2">
                <Label for="duration_days">Days (Umrah)</Label>
                <Input
                    id="duration_days"
                    v-model="form.duration_days"
                    type="number"
                    min="1"
                    placeholder="e.g. 21"
                />
                <InputError :message="form.errors.duration_days" />
            </div>
        </section>

        <section class="flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <h2 class="font-medium">Flights</h2>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="addLeg"
                    ><Plus /> Add flight</Button
                >
            </div>
            <InputError :message="form.errors.legs" />

            <div
                v-for="(leg, index) in form.legs"
                :key="index"
                class="panel grid gap-3 p-4 sm:grid-cols-6 lg:grid-cols-12"
            >
                <div class="grid gap-1.5 sm:col-span-2">
                    <Label :for="`flight-${index}`">Flight</Label>
                    <Input
                        :id="`flight-${index}`"
                        v-model="leg.flight_number"
                        placeholder="SV801"
                        class="uppercase"
                    />
                    <InputError :message="legError(index, 'flight_number')" />
                </div>
                <div class="grid gap-1.5 sm:col-span-1 lg:col-span-1">
                    <Label :for="`from-${index}`">From</Label>
                    <Input
                        :id="`from-${index}`"
                        v-model="leg.origin"
                        maxlength="3"
                        placeholder="MUX"
                        class="uppercase"
                    />
                    <InputError :message="legError(index, 'origin')" />
                </div>
                <div class="grid gap-1.5 sm:col-span-1 lg:col-span-1">
                    <Label :for="`to-${index}`">To</Label>
                    <Input
                        :id="`to-${index}`"
                        v-model="leg.destination"
                        maxlength="3"
                        placeholder="JED"
                        class="uppercase"
                    />
                    <InputError :message="legError(index, 'destination')" />
                </div>
                <div class="grid gap-1.5 sm:col-span-2 lg:col-span-3">
                    <Label :for="`dep-${index}`">Departs (local)</Label>
                    <Input
                        :id="`dep-${index}`"
                        v-model="leg.departs_at"
                        type="datetime-local"
                    />
                    <InputError :message="legError(index, 'departs_at')" />
                </div>
                <div class="grid gap-1.5 sm:col-span-2 lg:col-span-3">
                    <Label :for="`arr-${index}`">Arrives (local)</Label>
                    <Input
                        :id="`arr-${index}`"
                        v-model="leg.arrives_at"
                        type="datetime-local"
                    />
                    <InputError :message="legError(index, 'arrives_at')" />
                </div>
                <div class="grid gap-1.5 sm:col-span-2 lg:col-span-1">
                    <Label :for="`bag-${index}`">Baggage</Label>
                    <Input
                        :id="`bag-${index}`"
                        v-model="leg.baggage"
                        placeholder="23+7"
                    />
                </div>
                <div
                    class="flex items-end justify-between gap-2 sm:col-span-2 lg:col-span-1 lg:flex-col lg:items-start"
                >
                    <label class="flex items-center gap-2 text-sm">
                        <input
                            v-model="leg.meal"
                            type="checkbox"
                            class="size-4 rounded border-input"
                        />
                        Meal
                    </label>
                    <Button
                        v-if="form.legs.length > 1"
                        type="button"
                        variant="ghost"
                        size="icon-sm"
                        aria-label="Remove flight"
                        @click="removeLeg(index)"
                        ><Trash2
                    /></Button>
                </div>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="grid gap-2">
                <Label for="total_seats">Total seats</Label>
                <Input
                    id="total_seats"
                    v-model="form.total_seats"
                    type="number"
                    min="1"
                />
                <p
                    v-if="group && group.seats_taken > 0"
                    class="text-xs text-muted-foreground"
                >
                    {{ group.seats_taken }} already booked or on hold
                </p>
                <InputError :message="form.errors.total_seats" />
            </div>
            <div class="grid gap-2">
                <Label for="adult_fare">Adult fare (PKR)</Label>
                <Input
                    id="adult_fare"
                    v-model="form.adult_fare"
                    type="number"
                    min="0"
                />
                <p
                    v-if="Number(form.adult_fare) > 0"
                    class="text-xs text-muted-foreground"
                >
                    {{ formatPKR(Number(form.adult_fare)) }}
                </p>
                <InputError :message="form.errors.adult_fare" />
            </div>
            <div class="grid gap-2">
                <Label for="child_fare">Child fare (PKR)</Label>
                <Input
                    id="child_fare"
                    v-model="form.child_fare"
                    type="number"
                    min="0"
                />
                <InputError :message="form.errors.child_fare" />
            </div>
            <div class="grid gap-2">
                <Label for="infant_fare">Infant fare (PKR)</Label>
                <Input
                    id="infant_fare"
                    v-model="form.infant_fare"
                    type="number"
                    min="0"
                />
                <InputError :message="form.errors.infant_fare" />
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="grid gap-2">
                <Label for="supplier_pnr">Airline PNR (admin only)</Label>
                <Input
                    id="supplier_pnr"
                    v-model="form.supplier_pnr"
                    class="uppercase"
                />
                <InputError :message="form.errors.supplier_pnr" />
            </div>
            <div class="grid gap-2">
                <Label for="hold_minutes">Hold time (minutes)</Label>
                <Input
                    id="hold_minutes"
                    v-model="form.hold_minutes"
                    type="number"
                    min="5"
                    :placeholder="`Default ${defaultHoldMinutes}`"
                />
                <InputError :message="form.errors.hold_minutes" />
            </div>
            <div class="grid gap-2">
                <Label for="status">Status</Label>
                <NativeSelect id="status" v-model="form.status">
                    <option value="draft">Draft (hidden from agencies)</option>
                    <option value="published">Published</option>
                    <option value="closed">Closed</option>
                </NativeSelect>
                <InputError :message="form.errors.status" />
            </div>
        </section>

        <div class="grid gap-2">
            <Label for="notes">Notes for agencies</Label>
            <textarea
                id="notes"
                v-model="form.notes"
                rows="3"
                class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                placeholder="e.g. Name change not allowed after ticketing"
            ></textarea>
            <InputError :message="form.errors.notes" />
        </div>

        <div class="flex items-center gap-3">
            <Button type="submit" :disabled="form.processing">
                {{ editing ? 'Save changes' : 'Create group' }}
            </Button>
            <Button variant="ghost" as-child>
                <Link :href="admin.groups.index()">Cancel</Link>
            </Button>
        </div>
    </form>
</template>
