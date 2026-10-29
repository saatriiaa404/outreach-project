<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Archive,
    Bell,
    ClipboardList,
    Database,
    LayoutGrid,
    LockOpen,
    Signature,
    Users,
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
import { index as archiveIndex } from '@/routes/archive';
import { index as masterDataIndex } from '@/routes/master-data';
import { index as notificationsIndex } from '@/routes/notifications';
import { index as outreachIndex } from '@/routes/outreaches';
import { index as specialUnlockIndex } from '@/routes/special-unlock-requests';
import { index as templatesIndex } from '@/routes/templates';
import { index as usersIndex } from '@/routes/users';
import type { NavItem } from '@/types';

const page = usePage();

// Menu items follow the role; the server still authorizes every page.
const mainNavItems = computed<NavItem[]>(() => [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Outreach',
        href: outreachIndex(),
        icon: ClipboardList,
    },
    {
        title: page.props.unreadNotifications
            ? `Notifikasi (${page.props.unreadNotifications})`
            : 'Notifikasi',
        href: notificationsIndex(),
        icon: Bell,
    },
    ...(page.props.auth.user?.role === 'ADMIN'
        ? [
              {
                  title: 'Special Unlock',
                  href: specialUnlockIndex(),
                  icon: LockOpen,
              },
              {
                  title: 'Pengguna',
                  href: usersIndex(),
                  icon: Users,
              },
              {
                  title: 'Master Source/Jenis',
                  href: masterDataIndex(),
                  icon: Database,
              },
              {
                  title: 'Template TTD/Stempel',
                  href: templatesIndex(),
                  icon: Signature,
              },
              {
                  title: 'Arsip',
                  href: archiveIndex(),
                  icon: Archive,
              },
          ]
        : []),
]);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
