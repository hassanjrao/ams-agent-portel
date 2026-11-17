<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Timer } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { formatCountdown } from '@/lib/format';

// Time left on a hold, ticking every second. It turns red in the last 10 minutes and, if asked,
// reloads the page at zero so the booking shows as expired.
const props = withDefaults(
    defineProps<{
        seconds: number;
        reloadOnExpiry?: boolean;
        size?: 'sm' | 'lg';
    }>(),
    { reloadOnExpiry: false, size: 'sm' },
);

const startedAt = Date.now();
const now = ref(Date.now());
let timer: ReturnType<typeof setInterval> | undefined;

const left = computed(() =>
    Math.max(0, props.seconds - Math.floor((now.value - startedAt) / 1000)),
);
const urgent = computed(() => left.value < 600);

onMounted(() => {
    timer = setInterval(() => {
        now.value = Date.now();
        if (left.value === 0) {
            clearInterval(timer);
            if (props.reloadOnExpiry) {
                router.reload();
            }
        }
    }, 1000);
});
onBeforeUnmount(() => clearInterval(timer));
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-full font-semibold whitespace-nowrap tabular-nums"
        :class="[
            size === 'lg' ? 'px-3.5 py-1.5 text-base' : 'px-2 py-0.5 text-xs',
            left === 0
                ? 'bg-muted text-muted-foreground'
                : urgent
                  ? 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300'
                  : 'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200',
        ]"
    >
        <Timer :class="size === 'lg' ? 'size-4' : 'size-3'" />
        {{ left === 0 ? 'Hold ended' : formatCountdown(left) }}
    </span>
</template>
