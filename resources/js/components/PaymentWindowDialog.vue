<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Clock } from '@lucide/vue';
import { watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import admin from '@/routes/admin';

// Change how long agencies get to pay for a group's seats, straight from the groups list.
const open = defineModel<boolean>('open', { required: true });

const props = defineProps<{
    group: { id: number; sector: string; hold_minutes: number } | null;
    defaultMinutes: number;
}>();

const presets = [30, 50, 60, 120, 1440];

const form = useForm({ hold_minutes: 0 as number | '' });

watch(open, (isOpen) => {
    if (isOpen && props.group) {
        form.clearErrors();
        form.hold_minutes = props.group.hold_minutes;
    }
});

function label(minutes: number): string {
    if (minutes % 1440 === 0) {
        return `${minutes / 1440} day${minutes === 1440 ? '' : 's'}`;
    }

    return minutes % 60 === 0 && minutes >= 60
        ? `${minutes / 60} hour${minutes === 60 ? '' : 's'}`
        : `${minutes} min`;
}

function save() {
    if (!props.group) {
        return;
    }
    form.patch(admin.groups.holdMinutes.url(props.group.id), {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <form class="grid gap-5" @submit.prevent="save">
                <DialogHeader>
                    <div
                        class="mb-1 flex size-11 items-center justify-center rounded-full bg-gold/15 text-gold-dark ring-1 ring-gold/30"
                    >
                        <Clock class="size-5" />
                    </div>
                    <DialogTitle class="font-display text-2xl"
                        >Payment window</DialogTitle
                    >
                    <DialogDescription>
                        How long an agency has to pay for seats it holds on
                        <strong class="text-foreground">{{
                            group?.sector
                        }}</strong
                        >. Unpaid seats are released when the time runs out.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="payment_window">Minutes</Label>
                    <Input
                        id="payment_window"
                        v-model="form.hold_minutes"
                        type="number"
                        min="5"
                        max="2880"
                        required
                        autofocus
                    />
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            v-for="minutes in presets"
                            :key="minutes"
                            type="button"
                            class="rounded-full border px-3 py-1 text-xs font-medium transition hover:bg-muted"
                            :class="
                                form.hold_minutes === minutes
                                    ? 'border-gold bg-gold/15 text-foreground'
                                    : 'text-muted-foreground'
                            "
                            @click="form.hold_minutes = minutes"
                        >
                            {{ label(minutes) }}
                            <template v-if="minutes === defaultMinutes">
                                · default</template
                            >
                        </button>
                    </div>
                    <InputError :message="form.errors.hold_minutes" />
                </div>

                <p class="text-xs text-muted-foreground">
                    Applies to new holds. Seats already on hold keep the
                    deadline they were given.
                </p>

                <DialogFooter class="gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="open = false"
                        >Cancel</Button
                    >
                    <Button type="submit" :disabled="form.processing">
                        <Spinner v-if="form.processing" /> Save
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
