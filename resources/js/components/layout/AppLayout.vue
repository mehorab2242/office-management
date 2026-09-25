<script setup lang="ts">
import { ref } from 'vue';
import { LayoutDashboard, Menu, ReceiptText, Tags, X, LogOut, ChartNoAxesColumn, FileUp, Users, History, CircleDollarSign } from '@lucide/vue';
import { useAuthStore } from '../../stores/auth';
import { useRouter } from 'vue-router';
import { useToast } from '../../composables/useToast';

const auth = useAuthStore();
const router = useRouter();
const toast = useToast();
const mobileOpen = ref(false);

const links = [
    { name: 'dashboard', label: 'Dashboard', icon: LayoutDashboard },
    { name: 'expenses', label: 'Expenses', icon: ReceiptText },
    { name: 'report-monthly', label: 'Reports', icon: ChartNoAxesColumn, adminOnly: true },
    { name: 'earnings', label: 'Earnings', icon: CircleDollarSign, adminOnly: true },
];

async function handleLogout(): Promise<void> {
    await auth.signOut();
    toast.show('Logged out successfully');
    await router.replace({ name: 'login' });
}
</script>
<template>
    <div class="min-h-screen bg-slate-50 lg:grid lg:grid-cols-[16rem_1fr]">
        <button v-if="mobileOpen" class="fixed inset-0 z-30 bg-slate-950/40 lg:hidden" aria-label="Close navigation" @click="mobileOpen = false" />
        <aside class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r bg-slate-950 text-white transition lg:sticky lg:top-0 lg:h-screen lg:translate-x-0" :class="{ 'translate-x-0': mobileOpen }">
            <div class="flex h-16 items-center justify-between border-b border-white/10 px-5">
                <RouterLink :to="{ name: 'dashboard' }" class="font-semibold tracking-tight">Office Costs</RouterLink>
                <button class="rounded p-1 text-slate-300 lg:hidden" aria-label="Close navigation" @click="mobileOpen = false"><X class="size-5" /></button>
            </div>
            <nav class="flex-1 space-y-1 p-3" aria-label="Main navigation">
                <RouterLink v-for="link in links.filter((item) => !item.adminOnly || auth.isSuperAdmin)" :key="link.name" :to="{ name: link.name }" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-slate-300 hover:bg-white/10 hover:text-white" active-class="bg-brand-600 text-white" @click="mobileOpen = false">
                    <component :is="link.icon" class="size-5" />{{ link.label }}
                </RouterLink>
                <RouterLink :to="{ name: 'categories' }" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-slate-300 hover:bg-white/10 hover:text-white" active-class="bg-brand-600 text-white" @click="mobileOpen = false">
                    <Tags class="size-5" />Categories
                </RouterLink>
                <template v-if="auth.isSuperAdmin">
                    <RouterLink :to="{ name: 'imports' }" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-slate-300 hover:bg-white/10 hover:text-white" active-class="bg-brand-600 text-white" @click="mobileOpen = false"><FileUp class="size-5" />Import</RouterLink>
                    <RouterLink :to="{ name: 'users' }" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-slate-300 hover:bg-white/10 hover:text-white" active-class="bg-brand-600 text-white" @click="mobileOpen = false"><Users class="size-5" />Staff Management</RouterLink>
                    <RouterLink :to="{ name: 'audit-logs' }" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-slate-300 hover:bg-white/10 hover:text-white" active-class="bg-brand-600 text-white" @click="mobileOpen = false"><History class="size-5" />Audit log</RouterLink>
                </template>
            </nav>
            <div class="border-t border-white/10 p-4">
                <p class="truncate text-sm font-medium">{{ auth.user?.name }}</p>
                <p class="truncate text-xs text-slate-400">{{ auth.user?.email }}</p>
                <button class="mt-3 flex items-center gap-2 text-sm text-slate-300 hover:text-white" @click="handleLogout"><LogOut class="size-4" />Sign out</button>
            </div>
        </aside>
        <div class="min-w-0">
            <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b bg-white/95 px-4 backdrop-blur lg:px-8">
                <button class="rounded-lg border p-2 lg:hidden" aria-label="Open navigation" @click="mobileOpen = true"><Menu class="size-5" /></button>
                <div class="ml-auto flex items-center gap-3"><span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-medium capitalize text-brand-700">{{ auth.user?.role.replace('_', ' ') }}</span></div>
            </header>
            <main class="p-4 sm:p-6 lg:p-8"><slot /></main>
        </div>
    </div>
</template>
