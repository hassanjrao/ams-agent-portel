<script setup lang="ts">
// The top of every page: an optional gold eyebrow, a Playfair title, a line of context, actions.
defineProps<{
    title: string;
    description?: string;
    eyebrow?: string;
}>();
</script>

<template>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <p
                v-if="eyebrow"
                class="mb-1 text-[11px] font-bold tracking-[0.2em] text-highlight uppercase"
            >
                {{ eyebrow }}
            </p>
            <h1
                class="font-display text-2xl leading-tight font-semibold text-foreground sm:text-[1.7rem]"
            >
                {{ title }}
            </h1>
            <p v-if="description" class="mt-1 text-sm text-muted-foreground">
                {{ description }}
            </p>
            <slot name="meta" />
        </div>
        <div v-if="$slots.default" class="flex flex-wrap items-center gap-2">
            <slot />
        </div>
    </div>
</template>
