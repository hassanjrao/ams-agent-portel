<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpenText,
    Building2,
    CreditCard,
    Handshake,
    Landmark,
    LayoutGrid,
    Plane,
    PlaneTakeoff,
    ReceiptText,
    Search,
    Tags,
    Ticket,
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
} from '@/components/ui/sidebar';
import { dashboard, ledger } from '@/routes';
import admin from '@/routes/admin';
import bookings from '@/routes/bookings';
import groups from '@/routes/groups';
import payments from '@/routes/payments';
import type { NavItem } from '@/types';

const page = usePage();
const isAdmin = computed(() => page.props.auth.user?.role === 'admin');

const adminWork = computed<NavItem[]>(() => {
    const queue = page.props.adminQueue;

    return [
        {
            title: 'Overview',
            href: admin.dashboard(),
            icon: LayoutGrid,
            exact: true,
        },
        {
            title: 'Bookings',
            href: admin.bookings.index(),
            icon: Ticket,
            badge: queue?.holds,
        },
        {
            title: 'Payments',
            href: admin.payments.index(),
            icon: CreditCard,
            badge: queue?.payments,
        },
        {
            title: 'Agencies',
            href: admin.agencies.index(),
            icon: Building2,
            badge: queue?.agencies,
        },
        { title: 'Groups', href: admin.groups.index(), icon: PlaneTakeoff },
    ];
});

const adminSetup: NavItem[] = [
    { title: 'Airlines', href: admin.airlines.index(), icon: Plane },
    { title: 'Categories', href: admin.groupTypes.index(), icon: Tags },
    { title: 'Suppliers', href: admin.suppliers.index(), icon: Handshake },
    {
        title: 'Bank accounts',
        href: admin.bankAccounts.index(),
        icon: Landmark,
    },
];

const agentNav: NavItem[] = [
    { title: 'Dashboard', href: dashboard(), icon: LayoutGrid, exact: true },
    { title: 'Find groups', href: groups.index(), icon: Search },
    { title: 'My bookings', href: bookings.index(), icon: BookOpenText },
    { title: 'Payments', href: payments.index(), icon: ReceiptText },
    { title: 'Statement', href: ledger(), icon: Landmark },
];

const home = computed(() => (isAdmin.value ? admin.dashboard() : dashboard()));
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader class="px-3 pt-4 pb-2">
            <Link :href="home" class="flex justify-center">
                <AppLogo :subtitle="isAdmin ? 'Admin desk' : 'Agent portal'" />
            </Link>
        </SidebarHeader>

        <SidebarContent class="gap-1 pt-2">
            <template v-if="isAdmin">
                <NavMain :items="adminWork" label="Daily work" />
                <NavMain :items="adminSetup" label="Setup" />
            </template>
            <NavMain v-else :items="agentNav" label="Agency" />
        </SidebarContent>

        <SidebarFooter class="px-3 pb-3">
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
