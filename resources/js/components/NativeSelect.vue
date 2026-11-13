<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

// A plain <select> styled like the kit's Input: simpler than the popover Select for form fields.
const model = defineModel<string | number | null>();

defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <select
        v-model="model"
        :class="
            cn(
                'h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:opacity-50 aria-invalid:border-destructive md:text-sm dark:bg-input/30',
                $props.class,
            )
        "
    >
        <slot />
    </select>
</template>
