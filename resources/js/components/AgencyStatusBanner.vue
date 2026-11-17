<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Hourglass, ShieldAlert } from '@lucide/vue';
import { computed } from 'vue';

// Until AMS approves an agency (or after it is suspended) it can look, but not book or pay.
const agency = computed(() => usePage().props.myAgency);
</script>

<template>
    <div
        v-if="agency && agency.status !== 'active'"
        class="flex items-start gap-3 border-b px-4 py-3 text-sm md:px-6"
        :class="
            agency.status === 'pending'
                ? 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-200'
                : 'border-red-200 bg-red-50 text-red-900 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200'
        "
    >
        <component
            :is="agency.status === 'pending' ? Hourglass : ShieldAlert"
            class="mt-0.5 size-4 shrink-0"
        />
        <p v-if="agency.status === 'pending'">
            <strong>{{ agency.name }} is waiting for approval.</strong>
            You can browse groups and fares now. AMS will email you as soon as
            your agency can book.
        </p>
        <p v-else>
            <strong>{{ agency.name }} is suspended.</strong> New bookings and
            payments are paused. Please contact AMS Global Travel &amp; Tours.
        </p>
    </div>
</template>
