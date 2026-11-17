<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, CalendarDays } from '@lucide/vue';
import AirlineMark from '@/components/AirlineMark.vue';
import FlightLegs from '@/components/FlightLegs.vue';
import SeatsMeter from '@/components/SeatsMeter.vue';
import { Button } from '@/components/ui/button';
import { formatDate, formatPKR, formatWeekday } from '@/lib/format';
import groups from '@/routes/groups';
import type { GroupCard } from '@/types/portal';

// One group on sale, styled like a ticket: flights on the left, fare and seats on the stub.
defineProps<{ group: GroupCard }>();
</script>

<template>
    <article
        class="panel grid overflow-hidden transition hover:border-gold/40 hover:shadow-md md:grid-cols-[1fr_15rem]"
    >
        <div class="flex flex-col gap-4 p-5">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3">
                    <AirlineMark
                        :code="group.airline.code"
                        :name="group.airline.name"
                    />
                    <div>
                        <p class="text-lg leading-tight font-extrabold">
                            {{ group.sector }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ group.airline.name }}
                        </p>
                    </div>
                </div>
                <span
                    class="shrink-0 rounded-full bg-sand px-2.5 py-1 text-[11px] font-semibold text-ink-soft dark:bg-secondary dark:text-muted-foreground"
                    >{{ group.group_type }}</span
                >
            </div>

            <div
                class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted-foreground"
            >
                <span class="flex items-center gap-1.5">
                    <CalendarDays class="size-4 text-gold" />
                    <span class="font-semibold text-foreground">{{
                        formatDate(group.departure_date)
                    }}</span>
                    {{ formatWeekday(group.departure_date) }}
                </span>
                <span v-if="group.return_date"
                    >Return {{ formatDate(group.return_date) }}</span
                >
                <span
                    v-if="group.duration_days"
                    class="rounded-full border px-2 py-0.5 text-xs font-semibold text-foreground"
                    >{{ group.duration_days }} days</span
                >
            </div>

            <FlightLegs :legs="group.legs" compact />
        </div>

        <div
            class="relative flex flex-col justify-between gap-4 border-t border-dashed bg-muted/40 p-5 md:border-t-0 md:border-l"
        >
            <!-- Ticket notches where the stub tears off. -->
            <span
                class="absolute -top-2.5 -left-2.5 hidden size-5 rounded-full border bg-background md:block"
            />
            <span
                class="absolute -bottom-2.5 -left-2.5 hidden size-5 rounded-full border bg-background md:block"
            />
            <div>
                <p
                    class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    Adult fare
                </p>
                <p class="text-2xl font-extrabold tabular-nums">
                    {{ formatPKR(group.adult_fare) }}
                </p>
                <p class="text-xs text-muted-foreground">
                    Child {{ formatPKR(group.child_fare) }} · Infant
                    {{ formatPKR(group.infant_fare) }}
                </p>
            </div>
            <SeatsMeter
                :available="group.seats_available"
                :total="group.total_seats"
            />
            <Button v-if="group.seats_available > 0" as-child class="w-full">
                <Link :href="groups.show(group.id)"
                    >Book seats <ArrowRight
                /></Link>
            </Button>
            <Button v-else variant="outline" disabled class="w-full"
                >Sold out</Button
            >
        </div>
    </article>
</template>
