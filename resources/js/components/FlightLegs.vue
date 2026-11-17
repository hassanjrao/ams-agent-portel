<script setup lang="ts">
import { Luggage, Plane, Utensils } from '@lucide/vue';
import { formatShortDate } from '@/lib/format';
import type { Leg } from '@/types/portal';

// Each flight as a timeline: departure, a dashed line with the flight number, arrival.
// Times are the airports' local times.
defineProps<{ legs: Leg[]; compact?: boolean }>();
</script>

<template>
    <ol class="flex flex-col" :class="compact ? 'gap-2.5' : 'gap-4'">
        <li
            v-for="(leg, i) in legs"
            :key="i"
            class="grid grid-cols-[auto_1fr_auto] items-center gap-3"
        >
            <div class="text-left">
                <p
                    class="font-bold tabular-nums"
                    :class="compact ? 'text-base' : 'text-lg'"
                >
                    {{ leg.departs_time }}
                </p>
                <p class="text-xs whitespace-nowrap text-muted-foreground">
                    <span class="font-semibold text-foreground">{{
                        leg.origin
                    }}</span>
                    · {{ formatShortDate(leg.departs_on) }}
                </p>
            </div>
            <div class="flex min-w-0 flex-col items-center gap-1">
                <span
                    class="text-[11px] font-semibold tracking-wide text-muted-foreground"
                    >{{ leg.flight_number }}</span
                >
                <div class="flex w-full items-center gap-1 text-gold">
                    <span class="size-1.5 rounded-full bg-current" />
                    <span
                        class="h-px flex-1 border-t border-dashed border-current"
                    />
                    <Plane class="size-3.5" />
                    <span
                        class="h-px flex-1 border-t border-dashed border-current"
                    />
                    <span class="size-1.5 rounded-full bg-current" />
                </div>
                <span
                    v-if="!compact && (leg.baggage || leg.meal)"
                    class="flex items-center gap-2 text-[11px] text-muted-foreground"
                >
                    <span v-if="leg.baggage" class="flex items-center gap-1"
                        ><Luggage class="size-3" /> {{ leg.baggage }} kg</span
                    >
                    <span v-if="leg.meal" class="flex items-center gap-1"
                        ><Utensils class="size-3" /> Meal</span
                    >
                </span>
            </div>
            <div class="text-right">
                <p
                    class="font-bold tabular-nums"
                    :class="compact ? 'text-base' : 'text-lg'"
                >
                    {{ leg.arrives_time }}
                </p>
                <p class="text-xs whitespace-nowrap text-muted-foreground">
                    {{ formatShortDate(leg.arrives_on) }} ·
                    <span class="font-semibold text-foreground">{{
                        leg.destination
                    }}</span>
                </p>
            </div>
        </li>
    </ol>
</template>
