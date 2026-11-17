<script setup lang="ts">
// An airline's two-letter code in a gold ring, in place of a logo.
defineProps<{ code: string; name?: string; size?: 'sm' | 'md' }>();
</script>

<template>
    <span
        :title="name"
        class="flex shrink-0 items-center justify-center rounded-full border border-gold/40 bg-gradient-to-br from-cream to-sand font-extrabold tracking-wide text-gold-dark dark:from-secondary dark:to-muted dark:text-gold-light"
        :class="size === 'sm' ? 'size-8 text-[11px]' : 'size-11 text-sm'"
        >{{ code }}</span
    >
</template>
