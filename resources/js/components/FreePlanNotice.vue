<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Lock, MessageCircle } from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

// For AMS staff, once agencies have signed up beyond the free plan: a popup the first time in a
// browser session (and again whenever another agency is locked out), plus a header chip that
// reopens it.
const plan = computed(() => usePage().props.freePlan);
const open = ref(false);

// The popup comes back when the number of locked agencies changes, not on every page.
const seenKey = computed(() => `ams-free-plan-seen-${plan.value?.locked ?? 0}`);

onMounted(() => {
    if (!plan.value?.reached) {
        return;
    }
    try {
        if (!sessionStorage.getItem(seenKey.value)) {
            open.value = true;
        }
    } catch {
        open.value = true;
    }
});

function dismiss() {
    open.value = false;
    try {
        sessionStorage.setItem(seenKey.value, '1');
    } catch {
        // Private mode: the popup just shows again next time.
    }
}

const whatsapp = computed(() => {
    const p = plan.value;
    if (!p) {
        return '#';
    }
    const text = `Assalam-o-Alaikum, the AMS agent portal has reached its free limit of ${p.limit} agencies (${p.agencies} signed up). I would like to upgrade it.`;

    return `https://wa.me/${p.contact.whatsapp}?text=${encodeURIComponent(text)}`;
});
</script>

<template>
    <template v-if="plan?.reached">
        <button
            type="button"
            class="flex items-center gap-1.5 rounded-full border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-800 transition hover:bg-red-100 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-200"
            @click="open = true"
        >
            <Lock class="size-3.5" />
            Free limit reached
        </button>

        <Dialog
            :open="open"
            @update:open="
                (value: boolean) => (value ? (open = true) : dismiss())
            "
        >
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <div
                        class="mb-2 flex size-12 items-center justify-center rounded-full bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300"
                    >
                        <Lock class="size-5" />
                    </div>
                    <DialogTitle class="font-display text-2xl"
                        >Free limit reached</DialogTitle
                    >
                    <DialogDescription class="text-base">
                        The portal's free plan covers
                        <strong class="text-foreground"
                            >{{ plan.limit }} agencies</strong
                        >, and
                        <strong class="text-foreground">{{
                            plan.agencies
                        }}</strong>
                        have signed up.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid grid-cols-2 gap-3 text-center">
                    <div class="rounded-xl border bg-muted/50 p-3">
                        <p class="text-2xl font-extrabold tabular-nums">
                            {{ plan.limit }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            agencies can book and pay
                        </p>
                    </div>
                    <div
                        class="rounded-xl border border-red-200 bg-red-50 p-3 dark:border-red-900/60 dark:bg-red-950/40"
                    >
                        <p
                            class="text-2xl font-extrabold text-red-700 tabular-nums dark:text-red-300"
                        >
                            {{ plan.locked }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            can log in but not book or pay
                        </p>
                    </div>
                </div>

                <p class="text-sm text-muted-foreground">
                    Agencies that signed up after the first {{ plan.limit }} see
                    the portal but every action is blocked for them. Upgrade the
                    portal to let every agency book.
                </p>

                <DialogFooter class="gap-2 sm:justify-between">
                    <Button variant="outline" @click="dismiss">Later</Button>
                    <Button as-child>
                        <a
                            :href="whatsapp"
                            target="_blank"
                            rel="noopener noreferrer"
                            @click="dismiss"
                            ><MessageCircle /> Upgrade: WhatsApp
                            {{ plan.contact.name.split(' ')[0] }}</a
                        >
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </template>
</template>
