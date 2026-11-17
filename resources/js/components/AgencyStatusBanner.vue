<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Hourglass, Lock, ShieldAlert } from '@lucide/vue';
import { computed } from 'vue';

// Why an agency can look but not act: suspended, beyond the free plan, or waiting for approval.
// Suspension wins, then the free plan (approval alone wouldn't unlock it), then approval.
const agency = computed(() => usePage().props.myAgency);

const state = computed(() => {
    if (!agency.value) {
        return null;
    }
    if (agency.value.status === 'suspended') {
        return 'suspended';
    }
    if (agency.value.locked) {
        return 'locked';
    }

    return agency.value.status === 'pending' ? 'pending' : null;
});
</script>

<template>
    <div
        v-if="agency && state"
        class="flex items-start gap-3 border-b px-4 py-3 text-sm md:px-6"
        :class="
            state === 'pending'
                ? 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-200'
                : 'border-red-200 bg-red-50 text-red-900 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200'
        "
    >
        <component
            :is="
                state === 'pending'
                    ? Hourglass
                    : state === 'locked'
                      ? Lock
                      : ShieldAlert
            "
            class="mt-0.5 size-4 shrink-0"
        />
        <p v-if="state === 'pending'">
            <strong>{{ agency.name }} is waiting for approval.</strong>
            You can browse groups and fares now. AMS will email you as soon as
            your agency can book.
        </p>
        <p v-else-if="state === 'locked'">
            <strong>View only.</strong> {{ agency.lockedMessage }}
        </p>
        <p v-else>
            <strong>{{ agency.name }} is suspended.</strong> New bookings and
            payments are paused. Please contact AMS Global Travel &amp; Tours.
        </p>
    </div>
</template>
