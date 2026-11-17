<script setup lang="ts">
import { CalendarDays, Users } from '@lucide/vue';
import AirlineMark from '@/components/AirlineMark.vue';
import FlightLegs from '@/components/FlightLegs.vue';
import HoldCountdown from '@/components/HoldCountdown.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import {
    formatDate,
    formatDateTime,
    formatPKR,
    formatWeekday,
    paxSummary,
} from '@/lib/format';
import type { BookingDetail } from '@/types/portal';

// The booking as an itinerary: reference and status, flights, fare breakdown and passengers.
// Shared by the agency's booking page and AMS's.
defineProps<{ booking: BookingDetail }>();
</script>

<template>
    <section class="panel overflow-hidden">
        <!-- Header band: night with gold, like the website. -->
        <div
            class="relative flex flex-wrap items-center justify-between gap-4 bg-night px-5 py-4 text-white"
        >
            <span class="gold-rule absolute inset-x-0 bottom-0 h-0.5" />
            <div>
                <p
                    class="text-[10px] font-bold tracking-[0.2em] text-gold-light uppercase"
                >
                    Booking reference
                </p>
                <p class="font-mono text-xl font-bold tracking-wide">
                    {{ booking.reference }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span
                    v-if="booking.pnr"
                    class="rounded-full bg-white/10 px-3 py-1 font-mono text-sm font-bold"
                    >PNR {{ booking.pnr }}</span
                >
                <HoldCountdown
                    v-if="booking.seconds_left !== null"
                    :seconds="booking.seconds_left"
                    reload-on-expiry
                />
                <StatusBadge :status="booking.status" />
            </div>
        </div>

        <div class="grid gap-5 p-5 md:grid-cols-[1fr_16rem]">
            <div class="flex flex-col gap-4">
                <div class="flex items-center gap-3">
                    <AirlineMark
                        :code="booking.group.airline.code"
                        :name="booking.group.airline.name"
                    />
                    <div>
                        <p class="text-lg leading-tight font-extrabold">
                            {{ booking.group.sector }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{ booking.group.airline.name }} ·
                            {{ booking.group.group_type }}
                        </p>
                    </div>
                </div>
                <p
                    class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted-foreground"
                >
                    <span class="flex items-center gap-1.5"
                        ><CalendarDays class="size-4 text-gold" />
                        <span class="font-semibold text-foreground"
                            >{{ formatWeekday(booking.group.departure_date) }}
                            {{ formatDate(booking.group.departure_date) }}</span
                        ></span
                    >
                    <span class="flex items-center gap-1.5"
                        ><Users class="size-4 text-gold" />
                        {{
                            paxSummary(
                                booking.adults,
                                booking.children,
                                booking.infants,
                            )
                        }}</span
                    >
                </p>
                <FlightLegs :legs="booking.group.legs" />
            </div>

            <dl class="flex flex-col gap-2 rounded-xl bg-muted/50 p-4 text-sm">
                <div class="flex justify-between gap-2">
                    <dt class="text-muted-foreground">
                        {{ booking.adults }} × adult
                    </dt>
                    <dd class="tabular-nums">
                        {{ formatPKR(booking.adults * booking.adult_fare) }}
                    </dd>
                </div>
                <div v-if="booking.children" class="flex justify-between gap-2">
                    <dt class="text-muted-foreground">
                        {{ booking.children }} × child
                    </dt>
                    <dd class="tabular-nums">
                        {{ formatPKR(booking.children * booking.child_fare) }}
                    </dd>
                </div>
                <div v-if="booking.infants" class="flex justify-between gap-2">
                    <dt class="text-muted-foreground">
                        {{ booking.infants }} × infant
                    </dt>
                    <dd class="tabular-nums">
                        {{ formatPKR(booking.infants * booking.infant_fare) }}
                    </dd>
                </div>
                <div
                    class="mt-1 flex items-baseline justify-between gap-2 border-t pt-2"
                >
                    <dt class="font-semibold">Total</dt>
                    <dd class="text-xl font-extrabold tabular-nums">
                        {{ formatPKR(booking.total_amount) }}
                    </dd>
                </div>
                <p class="mt-auto pt-2 text-xs text-muted-foreground">
                    Booked by {{ booking.booked_by }},
                    {{ formatDateTime(booking.created_at) }}
                </p>
            </dl>
        </div>

        <div v-if="booking.agent_remarks" class="border-t px-5 py-3 text-sm">
            <span class="font-semibold">Agency note:</span>
            {{ booking.agent_remarks }}
        </div>
        <div
            v-if="booking.status === 'cancelled' && booking.cancel_reason"
            class="border-t bg-red-50 px-5 py-3 text-sm text-red-900 dark:bg-red-950/40 dark:text-red-200"
        >
            <span class="font-semibold">Cancelled:</span>
            {{ booking.cancel_reason }}
        </div>
    </section>
</template>
