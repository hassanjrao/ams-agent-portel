<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import DeveloperCredit from '@/components/DeveloperCredit.vue';
import { Toaster } from '@/components/ui/sonner';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="min-w-0 overflow-x-clip">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
            <footer
                class="mt-auto flex flex-col items-center justify-between gap-1 border-t px-4 py-4 text-xs text-muted-foreground sm:flex-row md:px-6"
            >
                <p>
                    &copy; {{ new Date().getFullYear() }} AMS Global Travel
                    &amp; Tours
                </p>
                <DeveloperCredit />
            </footer>
        </AppContent>
        <Toaster />
    </AppShell>
</template>
