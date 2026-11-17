<script setup lang="ts">
import { computed } from 'vue';
import { titleCase } from '@/lib/format';

// One colour per status, shared by groups, bookings, agencies and payments.
const props = defineProps<{ status: string }>();

const tones: Record<string, string> = {
    published:
        'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
    active: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
    confirmed:
        'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
    approved:
        'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
    on_hold:
        'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
    pending:
        'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
    draft: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
    closed: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
    expired:
        'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
    cancelled: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300',
    rejected: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300',
    suspended: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300',
};

const tone = computed(() => tones[props.status] ?? tones.draft);
</script>

<template>
    <span
        :class="tone"
        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap"
    >
        <span class="size-1.5 rounded-full bg-current opacity-70" />
        {{ titleCase(status) }}
    </span>
</template>
