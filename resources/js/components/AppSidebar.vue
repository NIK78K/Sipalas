<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, FolderGit2, LayoutGrid, Users, Building2, FileSpreadsheet, FileCheck, StickyNote, BarChart3, UploadCloud, ShieldCheck } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
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
import type { NavItem } from '@/types';

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: 'Daftar SPM',
            href: '/spm',
            icon: FileCheck,
        },
        {
            title: 'List Kelengkapan',
            href: '/kelengkapan',
            icon: BarChart3,
        },
        {
            title: 'Catatan Satker',
            href: '/catatan-satker',
            icon: StickyNote,
        },
    ];

    if (currentUser.value?.role === 'operator') {
        items.push({
            title: 'Pengguna',
            href: '/pengguna',
            icon: Users,
        });
    }

    if (currentUser.value?.role === 'operator' || currentUser.value?.role === 'admin') {
        items.push(
            {
                title: 'Master Satker',
                href: '/master/satker',
                icon: Building2,
            },
            {
                title: 'Master Akun Belanja',
                href: '/master/akun',
                icon: FileSpreadsheet,
            },
            {
                title: 'Impor Excel',
                href: '/impor',
                icon: UploadCloud,
            },
            {
                title: 'Log Aktivitas',
                href: '/log',
                icon: ShieldCheck,
            }
        );
    }

    return items;
});

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
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
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
