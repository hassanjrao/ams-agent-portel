<script setup lang="ts">
import { Check, Copy } from '@lucide/vue';
import { ref } from 'vue';
import type { BankAccount } from '@/types/portal';

// An AMS bank account an agency pays into, with one-tap copy of the account number and IBAN.
defineProps<{ account: BankAccount }>();

const copied = ref<string | null>(null);

async function copy(value: string) {
    try {
        await navigator.clipboard.writeText(value);
        copied.value = value;
        setTimeout(() => (copied.value = null), 1500);
    } catch {
        // Clipboard can be blocked (http, old browsers); the number is still on screen to copy by hand.
    }
}
</script>

<template>
    <div
        class="relative overflow-hidden rounded-xl border bg-gradient-to-br from-card to-muted/60 p-4"
    >
        <span class="gold-rule absolute inset-x-0 top-0 h-0.5" />
        <p class="font-bold">{{ account.bank_name }}</p>
        <p class="text-xs text-muted-foreground">
            {{ account.account_title
            }}<template v-if="account.branch"> · {{ account.branch }}</template>
        </p>
        <dl class="mt-3 grid gap-1.5 text-sm">
            <div
                v-for="row in [
                    { label: 'Account', value: account.account_number },
                    { label: 'IBAN', value: account.iban },
                ].filter((r) => r.value)"
                :key="row.label"
                class="flex items-center justify-between gap-2"
            >
                <dt class="text-xs text-muted-foreground">{{ row.label }}</dt>
                <dd class="flex min-w-0 items-center gap-1.5">
                    <span
                        class="truncate font-mono text-[13px] font-semibold"
                        >{{ row.value }}</span
                    >
                    <button
                        type="button"
                        class="rounded-md p-1 text-muted-foreground transition hover:bg-accent hover:text-foreground"
                        :aria-label="`Copy ${row.label}`"
                        @click="copy(row.value as string)"
                    >
                        <Check
                            v-if="copied === row.value"
                            class="size-3.5 text-emerald-600"
                        />
                        <Copy v-else class="size-3.5" />
                    </button>
                </dd>
            </div>
        </dl>
    </div>
</template>
