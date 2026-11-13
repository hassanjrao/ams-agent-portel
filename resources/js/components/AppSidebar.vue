<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Building2,
    Landmark,
    LayoutGrid,
    Plane,
    PlaneTakeoff,
    Tags,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import admin from '@/routes/admin';
import type { NavItem } from '@/types';

const page = usePage();
const isAdmin = computed(() => page.props.auth.user?.role === 'admin');

const adminNavItems: NavItem[] = [
    { title: 'Dashboard', href: admin.dashboard(), icon: LayoutGrid },
    { title: 'Groups', href: admin.groups.index(), icon: PlaneTakeoff },
    { title: 'Agencies', href: admin.agencies.index(), icon: Building2 },
    { title: 'Airlines', href: admin.airlines.index(), icon: Plane },
    { title: 'Categories', href: admin.groupTypes.index(), icon: Tags },
    {
        title: 'Bank accounts',
        href: admin.bankAccounts.index(),
        icon: Landmark,
    },
];

const agentNavItems: NavItem[] = [
    { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
];

const items = computed(() => (isAdmin.value ? adminNavItems : agentNavItems));
const home = computed(() => (isAdmin.value ? admin.dashboard() : dashboard()));
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="home">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="items" :label="isAdmin ? 'Admin' : 'Agency'" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
