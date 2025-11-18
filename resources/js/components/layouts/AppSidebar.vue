<script setup lang="ts">
import AppLogo from '@/components/common/AppLogo.vue';
import ThemeToggle from '@/components/common/ThemeToggle.vue';
import NavFooter from '@/components/layouts/NavFooter.vue';
import NavMain from '@/components/layouts/NavMain.vue';
import NavUser from '@/components/layouts/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarSeparator,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as accountsIndex } from '@/routes/accounts';
import { index as categoriesIndex } from '@/routes/categories';
import { index as rolesIndex } from '@/routes/roles';
import { index as usersIndex } from '@/routes/users';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, CreditCard, Folder, LayoutGrid, ShieldUser, Users } from 'lucide-vue-next';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Controle de Contas',
        href: accountsIndex(),
        icon: CreditCard,
    },
    {
        title: 'Categorias',
        href: categoriesIndex.url(),
        icon: Folder,
    },

    {
        title: 'Usuários',
        href: usersIndex.url(),
        icon: Users,
    },

    {
        title: 'Perfis & Permissões',
        href: rolesIndex.url(),
        icon: ShieldUser,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
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
            <SidebarSeparator />
            <div class="px-2 py-2">
                <div class="flex items-center justify-between group-data-[collapsible=icon]:justify-center">
                    <span class="text-sm text-muted-foreground group-data-[collapsible=icon]:hidden">Tema</span>
                    <ThemeToggle />
                </div>
            </div>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
