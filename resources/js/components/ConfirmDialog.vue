<script setup lang="ts">
import { ref, watch } from 'vue';
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

// "Are you sure?" for a quick action in a list, optionally asking for one line of text
// (a cancel reason, a PNR). The parent does the request and closes it on success.
const open = defineModel<boolean>('open', { required: true });

const props = withDefaults(
    defineProps<{
        title: string;
        description?: string;
        confirmLabel: string;
        destructive?: boolean;
        processing?: boolean;
        error?: string;
        // Shown only when given: the label of a one-line text field.
        inputLabel?: string;
        inputPlaceholder?: string;
        inputRequired?: boolean;
        initialValue?: string;
    }>(),
    {
        destructive: false,
        processing: false,
        inputRequired: false,
        initialValue: '',
    },
);

const emit = defineEmits<{ confirm: [value: string] }>();

const value = ref(props.initialValue);
watch(open, (isOpen) => {
    if (isOpen) {
        value.value = props.initialValue;
    }
});

function submit() {
    if (props.inputLabel && props.inputRequired && !value.value.trim()) {
        return;
    }
    emit('confirm', value.value.trim());
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription v-if="description">{{
                    description
                }}</DialogDescription>
            </DialogHeader>
            <form
                v-if="inputLabel"
                class="grid gap-1.5"
                @submit.prevent="submit"
            >
                <Label for="confirm-input">{{ inputLabel }}</Label>
                <Input
                    id="confirm-input"
                    v-model="value"
                    :placeholder="inputPlaceholder"
                    :required="inputRequired"
                />
            </form>
            <InputError :message="error" />
            <DialogFooter class="gap-2">
                <Button variant="outline" @click="open = false">Back</Button>
                <Button
                    :variant="destructive ? 'destructive' : 'default'"
                    :disabled="
                        processing ||
                        (!!inputLabel && inputRequired && !value.trim())
                    "
                    @click="submit"
                >
                    <Spinner v-if="processing" />
                    {{ confirmLabel }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
