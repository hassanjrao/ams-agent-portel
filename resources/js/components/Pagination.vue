<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { PaginationLink } from '@/types/portal';

defineProps<{
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
}>();

// Laravel labels the arrows with HTML entities.
const label = (text: string) =>
    text.replace('&laquo;', '‹').replace('&raquo;', '›');
</script>

<template>
    <div
        v-if="total > 0"
        class="flex flex-wrap items-center justify-between gap-3 text-sm text-muted-foreground"
    >
        <span>{{ from }}–{{ to }} of {{ total }}</span>
        <nav v-if="links.length > 3" class="flex flex-wrap gap-1">
            <template v-for="link in links" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    preserve-scroll
                    :class="
                        link.active
                            ? 'bg-primary text-primary-foreground'
                            : 'hover:bg-accent'
                    "
                    class="min-w-8 rounded-md border px-2.5 py-1 text-center"
                    >{{ label(link.label) }}</Link
                >
                <span
                    v-else
                    class="min-w-8 rounded-md border px-2.5 py-1 text-center opacity-40"
                    >{{ label(link.label) }}</span
                >
            </template>
        </nav>
    </div>
</template>
