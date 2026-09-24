<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { Download, Eye, Pencil, Plus, Search, Trash2 } from '@lucide/vue';
import AppLayout from '../components/layout/AppLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseDialog from '../components/ui/BaseDialog.vue';
import EmptyState from '../components/ui/EmptyState.vue';
import LoadingState from '../components/ui/LoadingState.vue';
import DatePicker from '../components/ui/DatePicker.vue';
import ExpenseDetailDialog from '../components/expenses/ExpenseDetailDialog.vue';
import { listCategories } from '../services/categories';
import { deleteExpense, listExpenses } from '../services/expenses';
import { downloadExport } from '../services/reports';
import { apiErrorMessage } from '../services/api';
import { useAuthStore } from '../stores/auth';
import { useToast } from '../composables/useToast';
import { formatDate, formatMoney } from '../utils/formatters';
import type { Category, Expense, PaginationMeta } from '../types';

const auth = useAuthStore(); const toast = useToast();
const expenses = ref<Expense[]>([]); const categories = ref<Category[]>([]);
const meta = ref<PaginationMeta>({ current_page: 1, last_page: 1, per_page: 15, total: 0 });
const loading = ref(true); const error = ref(''); const selected = ref<Expense | null>(null);
const detailOpen = ref(false); const deleteOpen = ref(false); const deleting = ref(false);
const filters = reactive({
    search: '', category_id: '', payment_method: '', date_from: '', date_to: '',
    amount_min: '', amount_max: '', sort: 'expense_date', direction: 'desc' as 'asc' | 'desc',
});
let searchTimer: number | undefined;
const pageNumbers = computed(() => Array.from({ length: meta.value.last_page }, (_, index) => index + 1)
    .filter((page) => Math.abs(page - meta.value.current_page) <= 2));
const showingFrom = computed(() => meta.value.total === 0 ? 0 : (meta.value.current_page - 1) * meta.value.per_page + 1);
const showingTo = computed(() => Math.min(meta.value.current_page * meta.value.per_page, meta.value.total));

async function load(page = 1): Promise<void> {
    loading.value = true; error.value = '';
    try {
        const response = await listExpenses({ page, per_page: meta.value.per_page, ...activeFilters() });
        expenses.value = response.data; meta.value = response.meta;
    } catch (caught) { error.value = apiErrorMessage(caught, 'Unable to load expenses.'); }
    finally { loading.value = false; }
}

function activeFilters() {
    return {
            search: filters.search || undefined,
            category_id: Number(filters.category_id) || undefined,
            payment_method: filters.payment_method || undefined,
            date_from: filters.date_from || undefined, date_to: filters.date_to || undefined,
            amount_min: Number(filters.amount_min) || undefined, amount_max: Number(filters.amount_max) || undefined,
            sort: filters.sort, direction: filters.direction,
        };
}

async function exportExpenses(): Promise<void> {
    try { await downloadExport('/exports/expenses.xlsx', activeFilters(), 'filtered-expenses.xlsx'); }
    catch (caught) { toast.show('Unable to export expenses', 'error', apiErrorMessage(caught)); }
}

function clearFilters(): void {
    Object.assign(filters, { search: '', category_id: '', payment_method: '', date_from: '', date_to: '', amount_min: '', amount_max: '', sort: 'expense_date', direction: 'desc' });
    load();
}

function showDetails(expense: Expense): void { selected.value = expense; detailOpen.value = true; }
function confirmDelete(expense: Expense): void { selected.value = expense; deleteOpen.value = true; }
function canEditExpense(expense: Expense): boolean { return auth.isSuperAdmin || expense.created_by?.id === auth.user?.id; }

async function remove(): Promise<void> {
    if (!selected.value) return;
    deleting.value = true;
    try { await deleteExpense(selected.value.id); deleteOpen.value = false; toast.show('Expense deleted'); await load(meta.value.current_page); }
    catch (caught) { toast.show('Unable to delete expense', 'error', apiErrorMessage(caught)); }
    finally { deleting.value = false; }
}

watch(() => filters.search, () => { window.clearTimeout(searchTimer); searchTimer = window.setTimeout(() => load(), 350); });
watch([
    () => filters.category_id, () => filters.payment_method,
    () => filters.date_from, () => filters.date_to, () => filters.amount_min, () => filters.amount_max,
    () => filters.sort, () => filters.direction,
], () => load());
onMounted(async () => { try { categories.value = await listCategories(); } catch { categories.value = []; } await load(); });
</script>
<template>
    <AppLayout>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-sm font-medium text-brand-700">Management</p><h1 class="mt-1 text-2xl font-semibold tracking-tight sm:text-3xl">Expenses</h1><p class="mt-1 text-sm text-slate-500">{{ meta.total }} recorded expenses</p></div><div class="flex flex-wrap gap-2"><BaseButton variant="secondary" @click="exportExpenses"><Download class="size-4" />Export Excel</BaseButton><RouterLink :to="{ name: 'expense-create' }" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700"><Plus class="size-4" />Add expense</RouterLink></div></div>
        <section class="card mt-6">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-8">
                <label class="relative xl:col-span-2"><span class="sr-only">Search expenses</span><Search class="pointer-events-none absolute left-3 top-3.5 size-4 text-slate-400" /><input v-model="filters.search" class="field mt-0 pl-9" placeholder="Search descriptions" /></label>
                <select v-model="filters.category_id" class="field mt-0" aria-label="Category"><option value="">All categories</option><option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option></select>
                <input v-model="filters.payment_method" class="field mt-0" aria-label="Payment method" placeholder="Payment method" />
                <DatePicker v-model="filters.date_from" class="mt-0" aria-label="From date" placeholder="From" />
                <DatePicker v-model="filters.date_to" class="mt-0" aria-label="To date" placeholder="To" :min-date="filters.date_from || undefined" />
                <input v-model="filters.amount_min" type="number" min="0" step="0.01" class="field mt-0" aria-label="Minimum amount" placeholder="Minimum amount" />
                <input v-model="filters.amount_max" type="number" min="0" step="0.01" class="field mt-0" aria-label="Maximum amount" placeholder="Maximum amount" />
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-3"><select v-model="filters.sort" class="field mt-0 w-auto" aria-label="Sort by"><option value="expense_date">Date</option><option value="amount">Amount</option><option value="description">Description</option></select><select v-model="filters.direction" class="field mt-0 w-auto" aria-label="Sort direction"><option value="desc">Descending</option><option value="asc">Ascending</option></select><button class="text-sm font-medium text-brand-700" @click="clearFilters">Clear filters</button></div>
        </section>
        <div v-if="error" class="card mt-6 text-center"><p class="text-red-700">{{ error }}</p><BaseButton class="mt-4" @click="load(meta.current_page)">Try again</BaseButton></div>
        <LoadingState v-else-if="loading" label="Loading expenses…" />
        <section v-else class="mt-6 overflow-hidden rounded-xl border bg-white">
            <EmptyState v-if="!expenses.length" title="No expenses found" description="Adjust the filters or add the first expense."><RouterLink :to="{ name: 'expense-create' }" class="text-sm font-semibold text-brand-700">Add expense</RouterLink></EmptyState>
            <div v-else>
                <div class="hidden overflow-x-auto md:block"><table class="w-full text-left text-sm"><thead class="border-b bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3 font-medium">Date</th><th class="px-5 py-3 font-medium">Description</th><th class="px-5 py-3 font-medium">Category</th><th class="px-5 py-3 font-medium">Payment method</th><th class="px-5 py-3 font-medium">Created by</th><th class="px-5 py-3 text-right font-medium">Amount</th><th class="px-5 py-3 text-right font-medium">Actions</th></tr></thead><tbody class="divide-y"><tr v-for="expense in expenses" :key="expense.id" class="hover:bg-slate-50"><td class="whitespace-nowrap px-5 py-4">{{ formatDate(expense.expense_date) }}</td><td class="max-w-xs px-5 py-4"><button class="truncate text-left font-medium hover:text-brand-700" @click="showDetails(expense)">{{ expense.description }}</button><p v-if="expense.reference" class="mt-0.5 truncate text-xs text-slate-500">{{ expense.reference }}</p></td><td class="px-5 py-4">{{ expense.category?.name ?? 'Uncategorized' }}</td><td class="px-5 py-4">{{ expense.payment_method ?? '—' }}</td><td class="px-5 py-4">{{ expense.created_by?.name ?? '—' }}</td><td class="whitespace-nowrap px-5 py-4 text-right font-semibold tabular-nums">{{ formatMoney(expense.amount) }}</td><td class="px-5 py-4"><div class="flex justify-end gap-1"><button class="rounded p-2 text-slate-500 hover:bg-slate-100" aria-label="View expense" @click="showDetails(expense)"><Eye class="size-4" /></button><RouterLink v-if="canEditExpense(expense)" :to="{ name: 'expense-edit', params: { id: expense.id } }" class="rounded p-2 text-slate-500 hover:bg-slate-100" aria-label="Edit expense"><Pencil class="size-4" /></RouterLink><button v-if="auth.isSuperAdmin" class="rounded p-2 text-red-600 hover:bg-red-50" aria-label="Delete expense" @click="confirmDelete(expense)"><Trash2 class="size-4" /></button></div></td></tr></tbody></table></div>
                <div class="divide-y md:hidden"><article v-for="expense in expenses" :key="expense.id" class="p-4"><div class="flex items-start justify-between gap-3"><button class="min-w-0 text-left" @click="showDetails(expense)"><p class="truncate font-medium">{{ expense.description }}</p><p class="mt-1 text-xs text-slate-500">{{ formatDate(expense.expense_date) }} · {{ expense.category?.name ?? 'Uncategorized' }}</p></button><p class="whitespace-nowrap font-semibold">{{ formatMoney(expense.amount) }}</p></div><div class="mt-3 flex justify-end"><div class="flex gap-2"><RouterLink v-if="canEditExpense(expense)" :to="{ name: 'expense-edit', params: { id: expense.id } }" class="rounded p-2 text-slate-500" aria-label="Edit expense"><Pencil class="size-4" /></RouterLink><button v-if="auth.isSuperAdmin" class="rounded p-2 text-red-600" aria-label="Delete expense" @click="confirmDelete(expense)"><Trash2 class="size-4" /></button></div></div></article></div>
                <footer class="flex flex-col gap-3 border-t bg-slate-50 px-4 py-3 text-sm sm:flex-row sm:items-center sm:justify-between"><span>Showing {{ showingFrom }}–{{ showingTo }} of {{ meta.total }}</span><div class="flex flex-wrap gap-2"><BaseButton variant="secondary" :disabled="meta.current_page <= 1" @click="load(meta.current_page - 1)">Previous</BaseButton><BaseButton v-for="page in pageNumbers" :key="page" :variant="page === meta.current_page ? 'primary' : 'secondary'" @click="load(page)">{{ page }}</BaseButton><BaseButton variant="secondary" :disabled="meta.current_page >= meta.last_page" @click="load(meta.current_page + 1)">Next</BaseButton></div></footer>
            </div>
        </section>
        <ExpenseDetailDialog v-model:open="detailOpen" :expense="selected" />
        <BaseDialog v-model:open="deleteOpen" title="Delete expense" description="This action permanently removes the expense record."><p class="mt-5 text-sm text-slate-600">Delete <strong>{{ selected?.description }}</strong>?</p><div class="mt-6 flex justify-end gap-3"><BaseButton variant="secondary" @click="deleteOpen = false">Cancel</BaseButton><BaseButton variant="danger" :loading="deleting" @click="remove">Delete expense</BaseButton></div></BaseDialog>
    </AppLayout>
</template>
