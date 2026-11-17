<script setup lang="ts">
import { computed } from 'vue';

// Seats left out of the block, as a bar that turns amber then red as the group fills.
const props = defineProps<{ available: number; total: number }>();

const sold = computed(() =>
    props.total > 0 ? (props.total - props.available) / props.total : 1,
);
const tone = computed(() => {
    if (props.available === 0) {
        return 'bg-muted-foreground/40';
    }
    if (props.available <= 5) {
        return 'bg-red-500';
    }

    return sold.value > 0.7 ? 'bg-amber-500' : 'bg-emerald-500';
});
const label = computed(() => {
    if (props.available === 0) {
        return 'Sold out';
    }

    return props.available <= 5
        ? `Only ${props.available} left`
        : `${props.available} seats left`;
});
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <div class="flex items-baseline justify-between gap-2 text-xs">
            <span
                class="font-bold"
                :class="
                    available === 0
                        ? 'text-muted-foreground'
                        : available <= 5
                          ? 'text-red-700 dark:text-red-400'
                          : 'text-foreground'
                "
            >
                {{ label }}
            </span>
            <span class="text-muted-foreground">of {{ total }}</span>
        </div>
        <div class="h-1.5 overflow-hidden rounded-full bg-muted">
            <div
                class="h-full rounded-full transition-all"
                :class="tone"
                :style="{ width: `${Math.round(sold * 100)}%` }"
            />
        </div>
    </div>
</template>
