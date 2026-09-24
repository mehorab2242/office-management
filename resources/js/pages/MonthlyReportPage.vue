<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue';
import AppLayout from '../components/layout/AppLayout.vue';
import ReportNav from '../components/reports/ReportNav.vue';
import SummaryCards from '../components/reports/SummaryCards.vue';
import BarChart from '../components/reports/BarChart.vue';
import LoadingState from '../components/ui/LoadingState.vue';
import DatePicker from '../components/ui/DatePicker.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import { monthlyReport, downloadExport } from '../services/reports';
import { listCategories } from '../services/categories';
import { apiErrorMessage } from '../services/api';
import { formatDate, formatMoney } from '../utils/formatters';
import type { Category, MonthlyReport } from '../types';

const period = ref(new Date().toISOString().slice(0, 7));
const report = ref<MonthlyReport | null>(null);
const categories = ref<Category[]>([]);
const loading = ref(true);
const error = ref('');
const filters = reactive({ search: '', category_id: '', payment_method: '', sort: 'expense_date', direction: 'desc' });

function query(page = 1): Record<string, unknown> {
    return { page, per_page: 15, ...Object.fromEntries(Object.entries(filters).filter(([, value]) => value !== '')) };
}
async function load(page = 1): Promise<void> {
    loading.value = true; error.value = '';
    const [year, month] = period.value.split('-').map(Number);
    try { report.value = await monthlyReport(year, month, query(page)); }
    catch (caught) { error.value = apiErrorMessage(caught); }
    finally { loading.value = false; }
}
async function exportFile(type: 'xlsx' | 'pdf'): Promise<void> {
    const [year, month] = period.value.split('-').map(Number);
    try { await downloadExport(`/exports/monthly.${type}`, { year, month }, `monthly-${period.value}.${type}`); }
    catch (caught) { error.value = apiErrorMessage(caught, 'Unable to export this report.'); }
}
function clearFilters(): void { Object.assign(filters, { search: '', category_id: '', payment_method: '', sort: 'expense_date', direction: 'desc' }); load(); }
watch(period, () => load()); onMounted(async () => { try { categories.value = await listCategories(); } catch { categories.value = []; } await load(); });
</script>
<template>
    <AppLayout>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-sm font-medium text-brand-700">Reports</p><h1 class="mt-1 text-3xl font-semibold">Monthly report</h1></div><ReportNav /></div>
        <section class="card mt-6 flex flex-wrap items-end gap-3"><label><span class="label">Month</span><DatePicker v-model="period" mode="month" aria-label="Report month" /></label><BaseButton variant="secondary" @click="exportFile('xlsx')">Export Excel</BaseButton><BaseButton variant="secondary" @click="exportFile('pdf')">Export PDF</BaseButton></section>
        <section class="card mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-7"><input v-model="filters.search" class="field mt-0 xl:col-span-2" placeholder="Search descriptions" aria-label="Search" /><select v-model="filters.category_id" class="field mt-0" aria-label="Category"><option value="">All categories</option><option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option></select><input v-model="filters.payment_method" class="field mt-0" placeholder="Payment method" aria-label="Payment method" /><select v-model="filters.sort" class="field mt-0" aria-label="Sort"><option value="expense_date">Date</option><option value="amount">Amount</option><option value="description">Description</option></select><select v-model="filters.direction" class="field mt-0" aria-label="Direction"><option value="desc">Descending</option><option value="asc">Ascending</option></select><div class="flex gap-2"><BaseButton @click="load()">Apply</BaseButton><BaseButton variant="secondary" @click="clearFilters">Clear</BaseButton></div></section>
        <p v-if="error" class="mt-5 rounded-lg bg-red-50 p-3 text-red-700">{{ error }}</p><LoadingState v-if="loading" label="Loading report…" />
        <template v-else-if="report"><div class="mt-6"><SummaryCards :summary="report" /></div><section class="card mt-6"><h2 class="font-semibold">Category breakdown</h2><BarChart class="mt-5" :items="report.categories.map(c => ({ label: c.name, total: c.total }))" /><p v-if="!report.categories.length" class="mt-4 text-sm text-slate-500">No expenses in this period.</p></section>
            <section class="mt-6 overflow-hidden rounded-xl border bg-white"><div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-slate-50"><tr><th class="p-3">Date</th><th class="p-3">Description</th><th class="p-3">Category</th><th class="p-3">Method</th><th class="p-3">Created by</th><th class="p-3 text-right">Amount</th></tr></thead><tbody class="divide-y"><tr v-for="expense in report.expenses" :key="expense.id"><td class="p-3">{{ formatDate(expense.expense_date) }}</td><td class="p-3 font-medium">{{ expense.description }}</td><td class="p-3">{{ expense.category?.name ?? 'Uncategorized' }}</td><td class="p-3">{{ expense.payment_method ?? '—' }}</td><td class="p-3">{{ expense.created_by?.name ?? '—' }}</td><td class="p-3 text-right font-semibold">{{ formatMoney(expense.amount) }}</td></tr></tbody></table></div><p v-if="!report.expenses.length" class="p-8 text-center text-sm text-slate-500">No transactions match these filters.</p><footer class="flex justify-end gap-2 border-t p-3"><BaseButton variant="secondary" :disabled="report.meta.current_page <= 1" @click="load(report.meta.current_page - 1)">Previous</BaseButton><span class="self-center text-sm">Page {{ report.meta.current_page }} of {{ report.meta.last_page }}</span><BaseButton variant="secondary" :disabled="report.meta.current_page >= report.meta.last_page" @click="load(report.meta.current_page + 1)">Next</BaseButton></footer></section>
        </template>
    </AppLayout>
</template>
