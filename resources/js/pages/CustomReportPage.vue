<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import AppLayout from '../components/layout/AppLayout.vue';
import ReportNav from '../components/reports/ReportNav.vue';
import SummaryCards from '../components/reports/SummaryCards.vue';
import BarChart from '../components/reports/BarChart.vue';
import LoadingState from '../components/ui/LoadingState.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import StatusBadge from '../components/ui/StatusBadge.vue';
import { customReport, downloadExport } from '../services/reports';
import { listCategories } from '../services/categories';
import { apiErrorMessage } from '../services/api';
import { formatDate, formatMoney } from '../utils/formatters';
import type { Category, ReportData } from '../types';
import type { ExpenseFilters } from '../services/expenses';

const filters = reactive<ExpenseFilters>({ search: '', date_from: '', date_to: '', category_id: undefined, payment_status: undefined, payment_method: '', amount_min: undefined, amount_max: undefined, per_page: 100 });
const categories = ref<Category[]>([]); const report = ref<ReportData | null>(null); const loading = ref(false); const error = ref('');
function params(): ExpenseFilters { return Object.fromEntries(Object.entries(filters).filter(([,v]) => v !== '' && v !== undefined)) as ExpenseFilters; }
async function load(): Promise<void> { loading.value = true; try { report.value = await customReport(params()); error.value = ''; } catch (e) { error.value = apiErrorMessage(e); } finally { loading.value = false; } }
async function exportFile(type: 'xlsx' | 'pdf'): Promise<void> { try { await downloadExport(`/exports/custom.${type}`, { ...params() }, `custom-report.${type}`); } catch (caught) { error.value = apiErrorMessage(caught, 'Unable to export this report.'); } }
function clear(): void { Object.assign(filters, { search: '', date_from: '', date_to: '', category_id: undefined, payment_status: undefined, payment_method: '', amount_min: undefined, amount_max: undefined, per_page: 100 }); load(); }
onMounted(async () => { try { categories.value = await listCategories(); } catch { categories.value = []; } await load(); });
</script>
<template>
    <AppLayout><div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-sm font-medium text-brand-700">Reports</p><h1 class="mt-1 text-3xl font-semibold">Custom report</h1></div><ReportNav /></div>
        <form class="card mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-5" @submit.prevent="load"><label><span class="label">Search</span><input v-model="filters.search" class="field" /></label><label><span class="label">From</span><input v-model="filters.date_from" type="date" class="field" /></label><label><span class="label">To</span><input v-model="filters.date_to" type="date" class="field" /></label><label><span class="label">Category</span><select v-model="filters.category_id" class="field"><option :value="undefined">All</option><option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option></select></label><label><span class="label">Status</span><select v-model="filters.payment_status" class="field"><option :value="undefined">All</option><option>paid</option><option>pending</option><option>unpaid</option><option>unspecified</option></select></label><label><span class="label">Payment method</span><input v-model="filters.payment_method" class="field" /></label><label><span class="label">Minimum</span><input v-model.number="filters.amount_min" type="number" class="field" min="0" /></label><label><span class="label">Maximum</span><input v-model.number="filters.amount_max" type="number" class="field" min="0" /></label><div class="flex items-end gap-2"><BaseButton type="submit">Apply</BaseButton><BaseButton variant="secondary" @click="clear">Clear</BaseButton></div></form>
        <div class="mt-3 flex gap-2"><BaseButton variant="secondary" @click="exportFile('xlsx')">Export Excel</BaseButton><BaseButton variant="secondary" @click="exportFile('pdf')">Export PDF</BaseButton></div><p v-if="error" class="mt-5 rounded-lg bg-red-50 p-3 text-red-700">{{ error }}</p><LoadingState v-if="loading" label="Building report…" />
        <template v-else-if="report"><div class="mt-6"><SummaryCards :summary="report.summary" /></div><section class="card mt-6"><h2 class="font-semibold">Category breakdown</h2><BarChart class="mt-4" :items="report.categories.map(category => ({ label: category.name, total: category.total }))" /><p v-if="!report.categories.length" class="mt-3 text-sm text-slate-500">No category totals match these filters.</p></section><section class="mt-6 overflow-hidden rounded-xl border bg-white"><div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-slate-50"><tr><th class="p-3">Date</th><th class="p-3">Description</th><th class="p-3">Category</th><th class="p-3">Status</th><th class="p-3">Method</th><th class="p-3 text-right">Amount</th></tr></thead><tbody class="divide-y"><tr v-for="expense in report.expenses" :key="expense.id"><td class="p-3">{{ formatDate(expense.expense_date) }}</td><td class="p-3 font-medium">{{ expense.description }}</td><td class="p-3">{{ expense.category?.name ?? 'Uncategorized' }}</td><td class="p-3"><StatusBadge :status="expense.payment_status" /></td><td class="p-3">{{ expense.payment_method ?? '—' }}</td><td class="p-3 text-right">{{ formatMoney(expense.amount) }}</td></tr></tbody></table></div><p v-if="!report.expenses?.length" class="p-8 text-center text-sm text-slate-500">No expenses match these filters.</p></section></template>
    </AppLayout>
</template>
