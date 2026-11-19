<script setup lang="ts">
import { Check, Copy } from '@lucide/vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import { copyText, groupText } from '@/lib/groupText';
import type { CopyableGroup } from '@/lib/groupText';

// Copies a group's airline, days, flights and adult fare as plain text, ready to paste.
const props = withDefaults(
    defineProps<{
        group: CopyableGroup;
        iconOnly?: boolean;
        size?: 'sm' | 'default' | 'icon-sm';
    }>(),
    { iconOnly: false, size: 'sm' },
);

const copied = ref(false);
let timer: ReturnType<typeof setTimeout> | undefined;

async function copy() {
    if (await copyText(groupText(props.group))) {
        copied.value = true;
        clearTimeout(timer);
        timer = setTimeout(() => (copied.value = false), 1800);
        toast.success('Group details copied');
    } else {
        toast.error("Couldn't copy. Select the details and copy them by hand.");
    }
}
</script>

<template>
    <Button
        type="button"
        variant="outline"
        :size="iconOnly ? 'icon-sm' : size"
        :aria-label="iconOnly ? 'Copy group details' : undefined"
        :title="iconOnly ? 'Copy group details' : undefined"
        @click="copy"
    >
        <component :is="copied ? Check : Copy" />
        <template v-if="!iconOnly">{{ copied ? 'Copied' : 'Copy' }}</template>
    </Button>
</template>
