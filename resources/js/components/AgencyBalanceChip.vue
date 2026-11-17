<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Wallet } from '@lucide/vue';
import { computed } from 'vue';
import { balanceLabel, formatPKR } from '@/lib/format';
import { ledger } from '@/routes';

// The agency's balance in the header, on every page: what it owes AMS, or the credit it holds.
const agency = computed(() => usePage().props.myAgency);
</script>

<template>
    <Link
        v-if="agency"
        :href="ledger()"
        class="group flex items-center gap-2.5 rounded-full border bg-card py-1 pr-3.5 pl-1 shadow-xs transition hover:border-gold/50"
    >
        <span
            class="flex size-7 items-center justify-center rounded-full bg-sand text-gold-dark dark:bg-secondary dark:text-gold-light"
        >
            <Wallet class="size-3.5" />
        </span>
        <span class="flex flex-col leading-tight">
            <span
                class="text-[10px] font-semibold tracking-wide text-muted-foreground uppercase"
                >{{ balanceLabel(agency.balance) }}</span
            >
            <span
                class="text-sm font-bold tabular-nums"
                :class="
                    agency.balance > 0
                        ? 'text-foreground'
                        : 'text-emerald-700 dark:text-emerald-400'
                "
                >{{ formatPKR(Math.abs(agency.balance)) }}</span
            >
        </span>
    </Link>
</template>
