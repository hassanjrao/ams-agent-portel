<script setup lang="ts">
import AgencyBalanceChip from '@/components/AgencyBalanceChip.vue';
import AgencyStatusBanner from '@/components/AgencyStatusBanner.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);
</script>

<template>
    <header
        class="sticky top-0 z-20 flex h-16 shrink-0 items-center justify-between gap-3 border-b bg-card/85 px-4 backdrop-blur-md transition-[width,height] ease-linear md:rounded-t-xl md:px-6"
    >
        <div class="flex min-w-0 items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <div class="hidden min-w-0 sm:block">
                <Breadcrumbs
                    v-if="breadcrumbs && breadcrumbs.length > 0"
                    :breadcrumbs="breadcrumbs"
                />
            </div>
        </div>
        <AgencyBalanceChip />
    </header>
    <AgencyStatusBanner />
</template>
