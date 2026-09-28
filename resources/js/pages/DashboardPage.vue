<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { ArrowDownRight, ArrowUpRight, Calculator, ReceiptText, Wallet } from '@lucide/vue';
import AppLayout from '../components/layout/AppLayout.vue';
import DatePicker from '../components/ui/DatePicker.vue';
import LoadingState from '../components/ui/LoadingState.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import { getDashboard } from '../services/dashboard';
import { apiErrorMessage } from '../services/api';
import { formatDate, formatMoney, formatMonth } from '../utils/formatters';
import type { DashboardData, FinancialSummary } from '../types';
import ExpenseFormDialog from '../components/expenses/ExpenseFormDialog.vue';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const dashboard = ref<DashboardData | null>(null);
const selectedMonth = ref(new Date().toISOString().slice(0, 7));
const loading = ref(true);
const error = ref('');
const expenseFormOpen = ref(false);
const isAdmin = computed(() => auth.isSuperAdmin);
const selectedSummary = computed(() => dashboard.value?.selected_month_summary);
const financial = computed(() => selectedSummary.value?.financial);
const previousFinancial = computed(() => dashboard.value?.previous_month_summary);

const staffStats = computed(() => {
    const data = selectedSummary.value ?? dashboard.value;
    if (!data) return [];
    return [
        { label: 'Your expenses', value: formatMoney(data.total_amount), icon: Wallet, tone: 'text-slate-700 bg-slate-100' },
        { label: 'Transactions', value: String(data.transaction_count), icon: ReceiptText, tone: 'text-brand-700 bg-brand-50' },
        { label: 'Average expense', value: formatMoney(data.average_amount ?? '0'), icon: Calculator, tone: 'text-violet-700 bg-violet-50' },
        { label: 'Largest expense', value: formatMoney(data.largest_amount ?? '0'), icon: ArrowUpRight, tone: 'text-amber-700 bg-amber-50' },
    ];
});

const financialStats = computed(() => {
    if (!financial.value) return [];
    return [
        { label: 'Total Revenue', value: formatMoney(financial.value.total_revenue), icon: ArrowUpRight, tone: 'text-emerald-700 bg-emerald-50', comparison: compare(previousFinancial.value?.total_revenue, financial.value.total_revenue) },
        { label: 'Total Cost', value: formatMoney(financial.value.total_cost), icon: Wallet, tone: 'text-amber-700 bg-amber-50', comparison: compare(previousFinancial.value?.total_cost, financial.value.total_cost) },
        { label: financial.value.is_loss ? 'Net Loss' : 'Net Profit', value: formatMoney(financial.value.net_profit), icon: financial.value.is_loss ? ArrowDownRight : ArrowUpRight, tone: financial.value.is_loss ? 'text-red-700 bg-red-50' : 'text-blue-700 bg-blue-50', comparison: compare(previousFinancial.value?.net_profit, financial.value.net_profit) },
        { label: 'Profit Margin', value: `${Number(financial.value.profit_margin).toFixed(2)}%`, icon: Calculator, tone: 'text-violet-700 bg-violet-50', comparison: marginComparison(previousFinancial.value, financial.value) },
    ];
});

const dailyMax = computed(() => Math.max(1, ...(dashboard.value?.selected_month_daily_trend ?? []).flatMap((day) => [Number(day.revenue), Number(day.cost)])));
const yearMax = computed(() => Math.max(1, ...(dashboard.value?.monthly_trend ?? []).flatMap((month) => [Number(month.revenue ?? 0), Number(month.cost ?? month.total)])));

function compare(previous: string | undefined, current: string): string | null {
    if (previous === undefined) return null;
    const before = Number(previous);
    const now = Number(current);
    if (before === 0) return now === 0 ? 'No change vs previous month' : 'Up from 0';
    const change = ((now - before) / Math.abs(before)) * 100;
    return `${change > 0 ? '+' : ''}${change.toFixed(1)}% vs previous month`;
}

function marginComparison(previous: FinancialSummary | null | undefined, current: FinancialSummary): string | null {
    if (!previous) return null;
    const change = Number(current.profit_margin) - Number(previous.profit_margin);
    return `${change > 0 ? '+' : ''}${change.toFixed(2)} pts vs previous month`;
}

async function load(): Promise<void> {
    loading.value = true;
    error.value = '';
    try {
        dashboard.value = await getDashboard(selectedMonth.value);
    } catch (caught) {
        error.value = apiErrorMessage(caught);
    } finally {
        loading.value = false;
    }
}

watch(selectedMonth, () => { void load(); });
onMounted(load);
</script>

<template>
    <AppLayout>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-brand-700">Overview</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight sm:text-3xl">{{ isAdmin ? 'Financial Dashboard' : 'Dashboard' }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ isAdmin ? `Office financial position for ${formatMonth(selectedMonth)}.` : `Your expense position for ${formatMonth(selectedMonth)}.` }}</p>
            </div>
            <div class="flex items-center gap-3"><label class="label" for="month">Reporting month</label><DatePicker id="month" v-model="selectedMonth" mode="month" aria-label="Reporting month" /></div>
        </div>
        <LoadingState v-if="loading" label="Loading dashboard…" />
        <div v-else-if="error && !dashboard" class="card mt-6 text-center"><p class="text-red-700">{{ error }}</p><BaseButton class="mt-4" @click="load">Try again</BaseButton></div>
        <template v-else>
            <p v-if="error" class="mt-6 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ error }}</p>

            <div v-if="isAdmin" class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article v-for="stat in financialStats" :key="stat.label" class="card min-w-0">
                    <div class="flex min-w-0 items-start justify-between gap-3">
                        <div class="min-w-0"><p class="text-sm text-slate-500">{{ stat.label }}</p><p class="mt-2 break-words text-xl font-semibold tracking-tight sm:text-2xl">{{ stat.value }}</p></div>
                        <span class="shrink-0 rounded-lg p-2" :class="stat.tone"><component :is="stat.icon" class="size-5" /></span>
                    </div>
                    <p v-if="stat.comparison" class="mt-3 text-xs text-slate-500">{{ stat.comparison }}</p>
                </article>
            </div>
            <div v-else class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article v-for="stat in staffStats" :key="stat.label" class="card min-w-0"><div class="flex min-w-0 items-start justify-between gap-3"><div class="min-w-0"><p class="text-sm text-slate-500">{{ stat.label }}</p><p class="mt-2 break-words text-xl font-semibold tracking-tight sm:text-2xl">{{ stat.value }}</p></div><span class="shrink-0 rounded-lg p-2" :class="stat.tone"><component :is="stat.icon" class="size-5" /></span></div></article>
            </div>

            <template v-if="isAdmin">
                <div class="mt-6 grid gap-6 xl:grid-cols-2">
                    <section class="card min-w-0">
                        <div><h2 class="font-semibold">Revenue vs Cost</h2><p class="text-sm text-slate-500">Daily totals for {{ formatMonth(selectedMonth) }}.</p></div>
                        <div class="mt-5 flex items-center gap-4 text-xs text-slate-500"><span class="flex items-center gap-2"><i class="size-2.5 rounded-full bg-emerald-500" />Revenue</span><span class="flex items-center gap-2"><i class="size-2.5 rounded-full bg-amber-500" />Cost</span></div>
                        <div class="mt-4 flex h-40 gap-1 overflow-x-auto border-b border-slate-100 pb-1" aria-label="Daily revenue and cost trend">
                            <div v-for="day in dashboard?.selected_month_daily_trend ?? []" :key="day.date" class="flex min-w-2 flex-1 items-end justify-center gap-px" :title="`${formatDate(day.date)} · Revenue ${formatMoney(day.revenue)} · Cost ${formatMoney(day.cost)}`">
                                <div class="w-1/2 rounded-t bg-emerald-500" :style="{ height: `${Math.max(2, Number(day.revenue) / dailyMax * 100)}%` }" />
                                <div class="w-1/2 rounded-t bg-amber-500" :style="{ height: `${Math.max(2, Number(day.cost) / dailyMax * 100)}%` }" />
                            </div>
                        </div>
                        <div class="mt-2 flex justify-between text-xs text-slate-400"><span>1</span><span>15</span><span>{{ dashboard?.selected_month_daily_trend?.length }}</span></div>
                    </section>
                    <section class="card">
                        <h2 class="font-semibold">Expense breakdown</h2><p class="text-sm text-slate-500">Where money was spent this month.</p>
                        <div v-if="dashboard?.selected_month_categories?.length" class="mt-5 space-y-4">
                            <div v-for="category in dashboard.selected_month_categories" :key="category.name"><div class="flex justify-between gap-3 text-sm"><span class="truncate font-medium">{{ category.name }}</span><span class="shrink-0 tabular-nums">{{ formatMoney(category.total) }}</span></div><div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-amber-500" :style="{ width: `${Math.max(2, Number(category.total) / Math.max(1, Number(financial?.total_cost)) * 100)}%` }" /></div></div>
                        </div>
                        <p v-else class="mt-6 text-sm text-slate-500">No expenses in this month.</p>
                    </section>
                    <section class="card">
                        <h2 class="font-semibold">Revenue sources</h2><p class="text-sm text-slate-500">Where money came from this month.</p>
                        <div v-if="dashboard?.selected_month_earning_sources?.length" class="mt-5 space-y-4">
                            <div v-for="source in dashboard.selected_month_earning_sources" :key="source.name"><div class="flex justify-between gap-3 text-sm"><span class="truncate font-medium">{{ source.name }}</span><span class="shrink-0 tabular-nums">{{ formatMoney(source.total) }}</span></div><div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-emerald-500" :style="{ width: `${Math.max(2, Number(source.total) / Math.max(1, Number(financial?.total_revenue)) * 100)}%` }" /></div></div>
                        </div>
                        <p v-else class="mt-6 text-sm text-slate-500">No earnings in this month.</p>
                    </section>
                    <section class="card">
                        <div><h2 class="font-semibold">Monthly financial trend</h2><p class="text-sm text-slate-500">Revenue and cost across {{ new Date().getFullYear() }}.</p></div>
                        <div class="mt-5 flex items-center gap-4 text-xs text-slate-500"><span class="flex items-center gap-2"><i class="size-2.5 rounded-full bg-emerald-500" />Revenue</span><span class="flex items-center gap-2"><i class="size-2.5 rounded-full bg-amber-500" />Cost</span></div>
                        <div class="mt-5 flex h-40 items-end gap-2 border-b border-slate-100 pb-1">
                            <div v-for="month in dashboard?.monthly_trend ?? []" :key="month.month" class="flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-2">
                                <div class="flex h-full w-full items-end justify-center gap-0.5"><div class="w-1/2 rounded-t bg-emerald-500" :style="{ height: `${Math.max(2, Number(month.revenue ?? 0) / yearMax * 100)}%` }" :title="`Revenue ${formatMoney(month.revenue ?? 0)}`" /><div class="w-1/2 rounded-t bg-amber-500" :style="{ height: `${Math.max(2, Number(month.cost ?? month.total) / yearMax * 100)}%` }" :title="`Cost ${formatMoney(month.cost ?? month.total)}`" /></div>
                                <span class="text-[10px] text-slate-400">{{ new Date(2000, month.month - 1).toLocaleString('en', { month: 'short' }) }}</span>
                            </div>
                        </div>
                    </section>
                </div>
                <section class="card mt-6">
                    <div class="flex items-center justify-between"><div><h2 class="font-semibold">Recent transactions</h2><p class="text-sm text-slate-500">Latest earnings and expenses across the office.</p></div><RouterLink :to="{ name: 'expenses' }" class="text-sm font-medium text-brand-700">View expenses</RouterLink></div>
                    <div class="mt-3 divide-y">
                        <div v-for="transaction in dashboard?.recent_transactions ?? []" :key="`${transaction.type}-${transaction.id}`" class="flex items-center gap-3 py-3">
                            <span class="grid size-9 shrink-0 place-items-center rounded-full" :class="transaction.type === 'earning' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'"><component :is="transaction.type === 'earning' ? ArrowDownRight : ArrowUpRight" class="size-4" /></span>
                            <div class="min-w-0 flex-1"><p class="truncate text-sm font-medium">{{ transaction.description }}</p><p class="text-xs text-slate-500">{{ formatDate(transaction.date) }} · {{ transaction.category }}</p></div>
                            <span class="text-right text-sm font-semibold tabular-nums" :class="transaction.type === 'earning' ? 'text-emerald-700' : 'text-slate-800'">{{ transaction.type === 'earning' ? '+' : '−' }}{{ formatMoney(transaction.amount) }}</span>
                        </div>
                        <p v-if="!dashboard?.recent_transactions?.length" class="py-6 text-sm text-slate-500">No recent transactions.</p>
                    </div>
                </section>
            </template>
            <template v-else>
                <div class="mt-6 grid gap-6 xl:grid-cols-[2fr_1fr]">
                    <section class="card"><div><h2 class="font-semibold">Your spending by category</h2><p class="text-sm text-slate-500">Your expense totals for the selected month.</p></div>
                        <div v-if="dashboard?.selected_month_categories?.length" class="mt-5 space-y-4"><div v-for="category in dashboard.selected_month_categories" :key="category.name" class="flex items-center justify-between border-b pb-3 last:border-0"><span class="text-sm font-medium">{{ category.name }}</span><span class="text-sm tabular-nums">{{ formatMoney(category.total) }}</span></div></div>
                        <p v-else class="mt-6 text-sm text-slate-500">No categorized expenses in this month.</p>
                    </section>
                    <section class="card bg-slate-950 text-white"><p class="text-sm text-slate-400">Quick action</p><h2 class="mt-2 text-xl font-semibold">Record a new expense</h2><p class="mt-2 text-sm text-slate-400">Add the receipt details while they are fresh.</p><button class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-brand-100" @click="expenseFormOpen = true">Add expense</button></section>
                </div>
                <div v-if="dashboard?.today && dashboard.current_year && dashboard.monthly_trend" class="mt-6 grid gap-6 xl:grid-cols-2">
                    <section class="card"><h2 class="font-semibold">Your expenses at a glance</h2><div class="mt-4 grid grid-cols-2 gap-4"><div class="rounded-lg bg-slate-50 p-4"><p class="text-xs uppercase tracking-wide text-slate-500">Today</p><p class="mt-1 text-lg font-semibold">{{ formatMoney(dashboard.today.total_amount) }}</p></div><div class="rounded-lg bg-slate-50 p-4"><p class="text-xs uppercase tracking-wide text-slate-500">Current year</p><p class="mt-1 text-lg font-semibold">{{ formatMoney(dashboard.current_year.total_amount) }}</p></div></div><div class="mt-5 flex h-32 items-end gap-2" aria-label="Your monthly spending trend"><div v-for="month in dashboard.monthly_trend" :key="month.month" class="flex h-full flex-1 items-end"><div class="w-full rounded-t bg-brand-500" :style="{ height: `${Math.max(3, Number(month.total) / Math.max(1, ...dashboard.monthly_trend.map(item => Number(item.total))) * 100)}%` }" :title="formatMoney(month.total)" /></div></div><div class="mt-2 flex justify-between text-xs text-slate-400"><span>Jan</span><span>Dec</span></div></section>
                    <section class="card"><div class="flex items-center justify-between"><h2 class="font-semibold">Your recent expenses</h2><RouterLink :to="{ name: 'expenses' }" class="text-sm font-medium text-brand-700">View all</RouterLink></div><div class="mt-4 divide-y"><div v-for="expense in dashboard.recent_expenses ?? []" :key="expense.id" class="flex items-center justify-between py-3"><div class="min-w-0"><p class="truncate text-sm font-medium">{{ expense.description }}</p><p class="text-xs text-slate-500">{{ expense.category?.name ?? 'Uncategorized' }}</p></div><span class="ml-3 text-sm font-semibold">{{ formatMoney(expense.amount) }}</span></div><p v-if="!dashboard.recent_expenses?.length" class="py-6 text-sm text-slate-500">No recent expenses.</p></div></section>
                </div>
            </template>
        </template>
        <ExpenseFormDialog v-model:open="expenseFormOpen" :expense-id="null" @saved="load" />
    </AppLayout>
</template>
