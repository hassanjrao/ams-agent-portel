<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from '@lucide/vue';
import { ArrowUpRight } from '@lucide/vue';

// A dashboard number. Linked tiles lift on hover; "attention" fills the icon gold when work is waiting.
defineProps<{
    label: string;
    value: string | number;
    hint?: string;
    icon: LucideIcon;
    href?: NonNullable<InertiaLinkProps['href']>;
    attention?: boolean;
}>();
</script>

<template>
    <component
        :is="href ? Link : 'div'"
        :href="href"
        class="panel group relative flex flex-col gap-3 p-4 sm:p-5"
        :class="
            href
                ? 'transition hover:-translate-y-0.5 hover:border-gold/40 hover:shadow-md'
                : ''
        "
    >
        <div class="flex items-center justify-between">
            <span
                class="flex size-9 items-center justify-center rounded-lg"
                :class="
                    attention
                        ? 'bg-gold text-night'
                        : 'bg-sand text-gold-dark dark:bg-secondary dark:text-gold-light'
                "
            >
                <component :is="icon" class="size-4.5" />
            </span>
            <ArrowUpRight
                v-if="href"
                class="size-4 text-muted-foreground opacity-0 transition group-hover:opacity-100"
            />
        </div>
        <div>
            <p class="text-xs font-medium text-muted-foreground sm:text-sm">
                {{ label }}
            </p>
            <p
                class="mt-0.5 text-lg font-extrabold tracking-tight tabular-nums sm:text-2xl"
            >
                {{ value }}
            </p>
            <p v-if="hint" class="mt-0.5 text-xs text-muted-foreground">
                {{ hint }}
            </p>
        </div>
    </component>
</template>
