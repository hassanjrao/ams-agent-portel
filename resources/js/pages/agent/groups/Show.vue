<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CalendarDays,
    Info,
    Lock,
    Minus,
    Plus,
    Timer,
} from '@lucide/vue';
import { computed, watch } from 'vue';
import AirlineMark from '@/components/AirlineMark.vue';
import FlightLegs from '@/components/FlightLegs.vue';
import InputError from '@/components/InputError.vue';
import PassengerEditor from '@/components/PassengerEditor.vue';
import SeatsMeter from '@/components/SeatsMeter.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { formatDate, formatPKR, formatWeekday } from '@/lib/format';
import { passengersPayload, syncPassengers } from '@/lib/passengers';
import bookings from '@/routes/bookings';
import groups from '@/routes/groups';
import type { GroupCard, PassengerDraft, PassengerType } from '@/types/portal';

const props = defineProps<{
    group: GroupCard;
    infantsTakeSeat: boolean;
}>();

defineOptions({
    layout: (props: { group: GroupCard }) => ({
        breadcrumbs: [
            { title: 'Find groups', href: groups.index() },
            { title: props.group.sector, href: groups.show(props.group.id) },
        ],
    }),
});

const agency = computed(() => usePage().props.myAgency);
const canBook = computed(
    () => agency.value?.status === 'active' && !agency.value.locked,
);

const form = useForm<{
    adults: number;
    children: number;
    infants: number;
    remarks: string;
    passengers: PassengerDraft[];
}>({
    adults: 1,
    children: 0,
    infants: 0,
    remarks: '',
    passengers: syncPassengers([], { adult: 1, child: 0, infant: 0 }),
});

watch(
    () => [form.adults, form.children, form.infants],
    () => {
        form.passengers = syncPassengers(form.passengers, {
            adult: form.adults,
            child: form.children,
            infant: form.infants,
        });
    },
);

const seats = computed(
    () =>
        form.adults +
        form.children +
        (props.infantsTakeSeat ? form.infants : 0),
);
const total = computed(
    () =>
        form.adults * props.group.adult_fare +
        form.children * props.group.child_fare +
        form.infants * props.group.infant_fare,
);

type Counter = {
    key: 'adults' | 'children' | 'infants';
    type: PassengerType;
    label: string;
    hint: string;
    fare: number;
};
const counters = computed<Counter[]>(() => [
    {
        key: 'adults',
        type: 'adult',
        label: 'Adults',
        hint: '12 years and over',
        fare: props.group.adult_fare,
    },
    {
        key: 'children',
        type: 'child',
        label: 'Children',
        hint: '2 to 11 years',
        fare: props.group.child_fare,
    },
    {
        key: 'infants',
        type: 'infant',
        label: 'Infants',
        hint: 'Under 2, on an adult’s lap',
        fare: props.group.infant_fare,
    },
]);

function canAdd(key: Counter['key']): boolean {
    if (key === 'infants') {
        // One infant per adult; infants only use a seat when the portal is set that way.
        return (
            form.infants < form.adults &&
            (!props.infantsTakeSeat ||
                seats.value < props.group.seats_available)
        );
    }

    return seats.value < props.group.seats_available;
}

function step(key: Counter['key'], by: number) {
    const min = key === 'adults' ? 1 : 0;
    form[key] = Math.max(min, form[key] + by);
    if (form.infants > form.adults) {
        form.infants = form.adults;
    }
}

function submit() {
    form.transform((data) => ({
        ...data,
        passengers: passengersPayload(data.passengers),
    })).post(bookings.store.url(props.group.id), { preserveScroll: true });
}

const errors = computed(() => form.errors as Partial<Record<string, string>>);
</script>

<template>
    <Head :title="`${group.sector} on ${formatDate(group.departure_date)}`" />

    <div class="flex flex-1 flex-col gap-5 p-4 md:p-6">
        <Link
            :href="groups.index()"
            class="flex w-fit items-center gap-1.5 text-sm font-semibold text-muted-foreground hover:text-foreground"
            ><ArrowLeft class="size-4" /> All groups</Link
        >

        <div class="grid items-start gap-6 lg:grid-cols-[1fr_22rem]">
            <div class="flex flex-col gap-5">
                <!-- The group -->
                <section class="panel overflow-hidden">
                    <div
                        class="flex flex-wrap items-center justify-between gap-4 border-b bg-gradient-to-r from-sand/70 to-card p-5 dark:from-secondary"
                    >
                        <div class="flex items-center gap-3">
                            <AirlineMark
                                :code="group.airline.code"
                                :name="group.airline.name"
                            />
                            <div>
                                <h1
                                    class="font-display text-2xl leading-tight font-semibold"
                                >
                                    {{ group.sector }}
                                </h1>
                                <p class="text-sm text-muted-foreground">
                                    {{ group.airline.name }} ·
                                    {{ group.group_type }}
                                </p>
                            </div>
                        </div>
                        <div class="text-right text-sm">
                            <p class="flex items-center gap-1.5 font-semibold">
                                <CalendarDays class="size-4 text-gold" />
                                {{ formatWeekday(group.departure_date) }}
                                {{ formatDate(group.departure_date) }}
                            </p>
                            <p
                                v-if="group.duration_days"
                                class="text-muted-foreground"
                            >
                                {{ group.duration_days }} days<template
                                    v-if="group.return_date"
                                >
                                    · back
                                    {{
                                        formatDate(group.return_date)
                                    }}</template
                                >
                            </p>
                        </div>
                    </div>
                    <div class="p-5">
                        <FlightLegs :legs="group.legs" />
                    </div>
                    <div
                        v-if="group.notes"
                        class="flex gap-2.5 border-t bg-muted/40 px-5 py-3.5 text-sm"
                    >
                        <Info class="mt-0.5 size-4 shrink-0 text-gold" />
                        <p class="whitespace-pre-line">{{ group.notes }}</p>
                    </div>
                </section>

                <!-- Who is travelling -->
                <section class="panel p-5">
                    <h2 class="font-display text-xl font-semibold">
                        Passengers
                    </h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Names exactly as in the passport. You can correct them
                        while the booking is on hold.
                    </p>

                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                        <div
                            v-for="counter in counters"
                            :key="counter.key"
                            class="flex items-center justify-between gap-2 rounded-xl border p-3"
                        >
                            <div>
                                <p class="text-sm font-semibold">
                                    {{ counter.label }}
                                </p>
                                <p class="text-[11px] text-muted-foreground">
                                    {{ counter.hint }}
                                </p>
                                <p class="text-xs font-semibold text-highlight">
                                    {{ formatPKR(counter.fare) }}
                                </p>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <Button
                                    type="button"
                                    size="icon-sm"
                                    variant="outline"
                                    :aria-label="`One fewer ${counter.type}`"
                                    :disabled="
                                        form[counter.key] <=
                                        (counter.key === 'adults' ? 1 : 0)
                                    "
                                    @click="step(counter.key, -1)"
                                    ><Minus
                                /></Button>
                                <span
                                    class="w-6 text-center text-lg font-bold tabular-nums"
                                    >{{ form[counter.key] }}</span
                                >
                                <Button
                                    type="button"
                                    size="icon-sm"
                                    variant="outline"
                                    :aria-label="`One more ${counter.type}`"
                                    :disabled="!canAdd(counter.key)"
                                    @click="step(counter.key, 1)"
                                    ><Plus
                                /></Button>
                            </div>
                        </div>
                    </div>
                    <InputError
                        class="mt-2"
                        :message="
                            form.errors.adults ||
                            form.errors.infants ||
                            errors.passengers ||
                            errors.group ||
                            errors.agency
                        "
                    />

                    <div class="mt-5">
                        <PassengerEditor
                            v-model="form.passengers"
                            :errors="errors"
                        />
                    </div>

                    <div class="mt-4 grid gap-1.5">
                        <Label for="remarks"
                            >Note for AMS
                            <span class="font-normal text-muted-foreground"
                                >(optional)</span
                            ></Label
                        >
                        <textarea
                            id="remarks"
                            v-model="form.remarks"
                            rows="2"
                            maxlength="500"
                            class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                            placeholder="e.g. wheelchair needed for one passenger"
                        ></textarea>
                    </div>
                </section>
            </div>

            <!-- Price and hold, kept in view while filling names. -->
            <aside class="panel sticky top-20 flex flex-col gap-4 p-5">
                <SeatsMeter
                    :available="group.seats_available"
                    :total="group.total_seats"
                />
                <dl class="grid gap-2 border-t pt-4 text-sm">
                    <div
                        v-for="counter in counters.filter(
                            (c) => form[c.key] > 0,
                        )"
                        :key="counter.key"
                        class="flex justify-between gap-2"
                    >
                        <dt class="text-muted-foreground">
                            {{ form[counter.key] }} ×
                            {{ counter.label.toLowerCase() }}
                        </dt>
                        <dd class="font-semibold tabular-nums">
                            {{ formatPKR(form[counter.key] * counter.fare) }}
                        </dd>
                    </div>
                </dl>
                <div
                    class="flex items-baseline justify-between gap-2 border-t pt-4"
                >
                    <span class="text-sm font-semibold">Total</span>
                    <span
                        class="text-gold-gradient text-3xl font-extrabold tabular-nums"
                        >{{ formatPKR(total) }}</span
                    >
                </div>
                <div
                    class="flex gap-2.5 rounded-xl bg-amber-50 p-3 text-xs text-amber-900 dark:bg-amber-950/40 dark:text-amber-200"
                >
                    <Timer class="mt-0.5 size-4 shrink-0" />
                    <p>
                        Seats are held for
                        <strong>{{ group.hold_minutes }} minutes</strong>. Send
                        payment before then and AMS confirms the booking.
                    </p>
                </div>
                <Button
                    size="lg"
                    class="w-full"
                    :disabled="
                        form.processing ||
                        !canBook ||
                        group.seats_available === 0
                    "
                    @click="submit"
                >
                    <Spinner v-if="form.processing" />
                    <Lock v-else />
                    Hold {{ seats }} seat{{ seats === 1 ? '' : 's' }}
                </Button>
                <p
                    v-if="!canBook"
                    class="text-center text-xs text-muted-foreground"
                >
                    {{
                        agency?.locked
                            ? 'Booking is not available for your agency on the free plan.'
                            : 'Booking opens once AMS approves your agency.'
                    }}
                </p>
            </aside>
        </div>
    </div>
</template>
