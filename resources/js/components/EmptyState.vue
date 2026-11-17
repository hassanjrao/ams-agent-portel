<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';

defineProps<{
    icon: LucideIcon;
    title: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex flex-col items-center justify-center gap-3 px-6 py-14 text-center"
    >
        <span
            class="flex size-12 items-center justify-center rounded-full bg-sand text-gold-dark dark:bg-secondary dark:text-gold-light"
        >
            <component :is="icon" class="size-5" />
        </span>
        <div>
            <p class="font-semibold">{{ title }}</p>
            <p
                v-if="description"
                class="mx-auto mt-1 max-w-sm text-sm text-muted-foreground"
            >
                {{ description }}
            </p>
        </div>
        <slot />
    </div>
</template>
