<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuBadge,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';

withDefaults(
    defineProps<{
        items: NavItem[];
        label?: string;
    }>(),
    { label: 'Menu' },
);

const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

// A section stays lit on its inner pages (a booking under Bookings); a dashboard only on itself.
function active(item: NavItem): boolean {
    return (
        item.isActive ??
        (item.exact ? isCurrentUrl(item.href) : isCurrentOrParentUrl(item.href))
    );
}
</script>

<template>
    <SidebarGroup class="px-3 py-1">
        <SidebarGroupLabel
            class="text-[10px] font-bold tracking-[0.18em] text-sidebar-foreground/45 uppercase"
            >{{ label }}</SidebarGroupLabel
        >
        <SidebarMenu class="gap-0.5">
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="active(item)"
                    :tooltip="item.title"
                    class="h-9 rounded-lg font-medium text-sidebar-foreground transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground data-[active=true]:bg-gold data-[active=true]:font-semibold data-[active=true]:text-night data-[active=true]:shadow-[0_4px_14px_-6px_rgb(200_150_43/0.7)]"
                >
                    <Link :href="item.href">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
                <SidebarMenuBadge
                    v-if="item.badge"
                    class="rounded-full bg-gold-light/20 px-1.5 text-[11px] font-bold text-gold-light peer-data-[active=true]/menu-button:bg-night/15 peer-data-[active=true]/menu-button:text-night"
                    >{{ item.badge }}</SidebarMenuBadge
                >
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
