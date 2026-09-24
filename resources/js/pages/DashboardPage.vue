<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { ArrowRight, ArrowUp, Calculator, CircleDollarSign, ReceiptText } from '@lucide/vue';
import AppLayout from '../components/layout/AppLayout.vue';
import DatePicker from '../components/ui/DatePicker.vue';
import LoadingState from '../components/ui/LoadingState.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import { getDashboard } from '../services/dashboard';
import { apiErrorMessage } from '../services/api';
import { formatMoney, formatMonth } from '../utils/formatters';
import type { DashboardData } from '../types';

const dashboard = ref<DashboardData | null>(null);
const selectedMonth = ref(new Date().toISOString().slice(0, 7));
const loading = ref(true);
const error = ref('');

const stats = computed(() => {
    const data = dashboard.value?.selected_month_summary ?? dashboard.value;
    if (!data) return [];
    return [
        { label: 'Total expenses', value: formatMoney(data.total_amount), icon: CircleDollarSign, tone: 'text-slate-700 bg-slate-100' },
        { label: 'Transactions', value: String(data.transaction_count), icon: ReceiptText, tone: 'text-brand-700 bg-brand-50' },
        { label: 'Average expense', value: formatMoney(data.average_amount ?? '0'), icon: Calculator, tone: 'text-violet-700 bg-violet-50' },
        { label: 'Largest expense', value: formatMoney(data.largest_amount ?? '0'), icon: ArrowUp, tone: 'text-amber-700 bg-amber-50' },
    ];
});

async function load(): Promise<void> {
    loading.value = true; error.value = '';
    try {
        dashboard.value = await getDashboard(selectedMonth.value);
    } catch (caught) { error.value = apiErrorMessage(caught); }
    finally { loading.value = false; }
}

watch(selectedMonth, () => { void load(); });

onMounted(load);
</script>
<template>
    <AppLayout>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="text-sm font-medium text-brand-700">Overview</p><h1 class="mt-1 text-2xl font-semibold tracking-tight sm:text-3xl">Dashboard</h1><p class="mt-1 text-sm text-slate-500">Your expense position for {{ formatMonth(selectedMonth) }}.</p></div>
            <div class="flex items-center gap-3"><label class="label" for="month">Reporting month</label><DatePicker id="month" v-model="selectedMonth" mode="month" aria-label="Reporting month" /></div>
        </div>
        <LoadingState v-if="loading" label="Loading dashboard…" />
        <div v-else-if="error && !dashboard" class="card mt-6 text-center"><p class="text-red-700">{{ error }}</p><BaseButton class="mt-4" @click="load">Try again</BaseButton></div>
        <template v-else>
            <p v-if="error" class="mt-6 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ error }}</p>
            <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article v-for="stat in stats" :key="stat.label" class="card"><div class="flex items-start justify-between"><div><p class="text-sm text-slate-500">{{ stat.label }}</p><p class="mt-2 text-2xl font-semibold tracking-tight">{{ stat.value }}</p></div><span class="rounded-lg p-2" :class="stat.tone"><component :is="stat.icon" class="size-5" /></span></div></article>
            </div>
            <div class="mt-6 grid gap-6 xl:grid-cols-[2fr_1fr]">
                <section class="card"><div class="flex items-center justify-between"><div><h2 class="font-semibold">Spending by category</h2><p class="text-sm text-slate-500">Category totals for the selected month.</p></div></div>
                    <div v-if="dashboard?.selected_month_categories?.length" class="mt-5 space-y-4"><div v-for="category in dashboard.selected_month_categories" :key="category.name" class="flex items-center justify-between border-b pb-3 last:border-0"><span class="text-sm font-medium">{{ category.name }}</span><span class="text-sm tabular-nums">{{ formatMoney(category.total) }}</span></div></div>
                    <p v-else class="mt-6 text-sm text-slate-500">No categorized expenses in this month.</p>
                </section>
                <section class="card bg-slate-950 text-white"><p class="text-sm text-slate-400">Quick action</p><h2 class="mt-2 text-xl font-semibold">Record a new expense</h2><p class="mt-2 text-sm text-slate-400">Add the receipt details while they are fresh.</p><RouterLink :to="{ name: 'expense-create' }" class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-brand-100">Add expense <ArrowRight class="size-4" /></RouterLink></section>
            </div>
            <div v-if="dashboard?.today && dashboard.current_year && dashboard.monthly_trend && dashboard.recent_expenses" class="mt-6 grid gap-6 xl:grid-cols-2">
                <section class="card"><h2 class="font-semibold">This year at a glance</h2><div class="mt-4 grid grid-cols-2 gap-4"><div class="rounded-lg bg-slate-50 p-4"><p class="text-xs uppercase tracking-wide text-slate-500">Today</p><p class="mt-1 text-lg font-semibold">{{ formatMoney(dashboard.today.total_amount) }}</p></div><div class="rounded-lg bg-slate-50 p-4"><p class="text-xs uppercase tracking-wide text-slate-500">Current year</p><p class="mt-1 text-lg font-semibold">{{ formatMoney(dashboard.current_year.total_amount) }}</p></div></div><div class="mt-5 flex h-32 items-end gap-2" aria-label="Monthly spending trend"><div v-for="month in dashboard.monthly_trend" :key="month.month" class="flex h-full flex-1 items-end"><div class="w-full rounded-t bg-brand-500" :style="{ height: `${Math.max(3, Number(month.total) / Math.max(1, ...dashboard.monthly_trend.map(item => Number(item.total))) * 100)}%` }" :title="formatMoney(month.total)" /></div></div><div class="mt-2 flex justify-between text-xs text-slate-400"><span>Jan</span><span>Dec</span></div></section>
                <section class="card"><div class="flex items-center justify-between"><h2 class="font-semibold">Recent expenses</h2><RouterLink :to="{ name: 'expenses' }" class="text-sm font-medium text-brand-700">View all</RouterLink></div><div class="mt-4 divide-y"><div v-for="expense in dashboard.recent_expenses" :key="expense.id" class="flex items-center justify-between py-3"><div class="min-w-0"><p class="truncate text-sm font-medium">{{ expense.description }}</p><p class="text-xs text-slate-500">{{ expense.category?.name ?? 'Uncategorized' }}</p></div><span class="ml-3 text-sm font-semibold">{{ formatMoney(expense.amount) }}</span></div><p v-if="!dashboard.recent_expenses.length" class="py-6 text-sm text-slate-500">No recent expenses.</p></div></section>
            </div>
        </template>
    </AppLayout>
</template>
