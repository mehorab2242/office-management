<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { HandCoins, Pencil, Plus, Trash2, Wallet, ReceiptText } from '@lucide/vue';
import AppLayout from '../components/layout/AppLayout.vue';
import BaseDialog from '../components/ui/BaseDialog.vue';
import DatePicker from '../components/ui/DatePicker.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import LoadingState from '../components/ui/LoadingState.vue';
import { createOnHandReceipt, deleteOnHandReceipt, listOnHandReceipts, updateOnHandReceipt } from '../services/onHand';
import { apiErrorMessage, validationErrors } from '../services/api';
import { useToast } from '../composables/useToast';
import { formatDate, formatMoney } from '../utils/formatters';
import type { OnHandReceipt, OnHandReceiptPayload, OnHandSummary, PaginationMeta, ValidationErrors } from '../types';

const toast = useToast();
const rows = ref<OnHandReceipt[]>([]);
const summary = ref<OnHandSummary>({ total_received: '0.00', total_expenses: '0.00', current_on_hand: '0.00' });
const meta = ref<PaginationMeta>({ current_page: 1, last_page: 1, per_page: 15, total: 0 });
const loading = ref(true);
const saving = ref(false);
const error = ref('');
const formError = ref('');
const formErrors = ref<ValidationErrors>({});
const formOpen = ref(false);
const editing = ref<OnHandReceipt | null>(null);
const filters = reactive({ date_from: '', date_to: '' });
const today = new Date();
const todayDate = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
const form = reactive<OnHandReceiptPayload>({ received_date: todayDate, amount: 0, note: null });
const isNegative = computed(() => Number(summary.value.current_on_hand) < 0);
const stats = computed(() => [
    { label: 'Total Received', value: formatMoney(summary.value.total_received), icon: HandCoins, tone: 'text-emerald-700 bg-emerald-50', valueTone: '' },
    { label: 'Total Expenses', value: formatMoney(summary.value.total_expenses), icon: ReceiptText, tone: 'text-amber-700 bg-amber-50', valueTone: '' },
    { label: 'Current On Hand', value: formatMoney(summary.value.current_on_hand), icon: Wallet, tone: isNegative.value ? 'text-red-700 bg-red-50' : 'text-brand-700 bg-brand-50', valueTone: isNegative.value ? 'text-red-700' : '' },
]);
const pageNumbers = computed(() => Array.from({ length: meta.value.last_page }, (_, index) => index + 1)
    .filter((page) => Math.abs(page - meta.value.current_page) <= 2));
const showingFrom = computed(() => meta.value.total === 0 ? 0 : (meta.value.current_page - 1) * meta.value.per_page + 1);
const showingTo = computed(() => Math.min(meta.value.current_page * meta.value.per_page, meta.value.total));

async function load(page = 1): Promise<void> {
    loading.value = true;
    try {
        const response = await listOnHandReceipts({ ...filters, page, per_page: meta.value.per_page });
        rows.value = response.data;
        meta.value = response.meta;
        summary.value = response.summary;
        error.value = '';
    }
    catch (caught) { error.value = apiErrorMessage(caught); }
    finally { loading.value = false; }
}

function startCreate(): void {
    editing.value = null;
    Object.assign(form, { received_date: todayDate, amount: 0, note: null });
    formErrors.value = {};
    formError.value = '';
    formOpen.value = true;
}

function startEdit(row: OnHandReceipt): void {
    editing.value = row;
    Object.assign(form, { received_date: row.received_date, amount: Number(row.amount), note: row.note });
    formErrors.value = {};
    formError.value = '';
    formOpen.value = true;
}

async function save(): Promise<void> {
    saving.value = true;
    formErrors.value = {};
    formError.value = '';
    try {
        const payload = { ...form, note: form.note?.trim() || null };
        if (editing.value) { await updateOnHandReceipt(editing.value.id, payload); toast.show('Received money updated'); }
        else { await createOnHandReceipt(payload); toast.show('Received money recorded'); }
        formOpen.value = false;
        await load(meta.value.current_page);
    } catch (caught) { formErrors.value = validationErrors(caught); formError.value = apiErrorMessage(caught); }
    finally { saving.value = false; }
}

async function remove(row: OnHandReceipt): Promise<void> {
    if (!confirm(`Delete the ${formatMoney(row.amount)} received on ${formatDate(row.received_date)}?`)) return;
    try { await deleteOnHandReceipt(row.id); toast.show('Received money deleted'); await load(meta.value.current_page); }
    catch (caught) { error.value = apiErrorMessage(caught); }
}

onMounted(load);
</script>

<template>
    <AppLayout>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="text-sm font-medium text-brand-700">Finance</p><h1 class="mt-1 text-3xl font-semibold">On Hand</h1><p class="mt-1 text-sm text-slate-500">Money you received for office expenses. Your recorded expenses are deducted automatically.</p></div>
            <BaseButton @click="startCreate"><Plus class="size-4" />Add received money</BaseButton>
        </div>
        <div class="mt-6 grid gap-4 sm:grid-cols-3">
            <article v-for="stat in stats" :key="stat.label" class="card min-w-0"><div class="flex min-w-0 items-start justify-between gap-3"><div class="min-w-0"><p class="text-sm text-slate-500">{{ stat.label }}</p><p class="mt-2 break-words text-xl font-semibold tracking-tight tabular-nums sm:text-2xl" :class="stat.valueTone">{{ stat.value }}</p></div><span class="shrink-0 rounded-lg p-2" :class="stat.tone"><component :is="stat.icon" class="size-5" /></span></div></article>
        </div>
        <form class="card mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-3" @submit.prevent="load()">
            <label><span class="label">From</span><DatePicker v-model="filters.date_from" placeholder="Start date" /></label>
            <label><span class="label">To</span><DatePicker v-model="filters.date_to" placeholder="End date" /></label>
            <div class="flex items-end gap-2"><BaseButton type="submit">Filter</BaseButton><BaseButton type="button" variant="secondary" @click="Object.assign(filters, { date_from: '', date_to: '' }); load()">Clear</BaseButton></div>
        </form>
        <p v-if="error" class="mt-5 rounded-lg bg-red-50 p-3 text-red-700">{{ error }}</p>
        <LoadingState v-if="loading" label="Loading received money…" />
        <section v-else class="mt-6 overflow-hidden rounded-xl border bg-white">
            <div class="flex flex-col gap-1 border-b px-5 py-4 sm:flex-row sm:items-center sm:justify-between"><h2 class="font-semibold">Money received history</h2><p class="text-sm text-slate-500">Current balance <span class="font-semibold tabular-nums" :class="isNegative ? 'text-red-700' : 'text-slate-900'">{{ formatMoney(summary.current_on_hand) }}</span></p></div>
            <div class="hidden overflow-x-auto md:block"><table class="w-full min-w-[36rem] text-left text-sm"><thead class="border-b bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3 font-medium">Date</th><th class="px-5 py-3 font-medium">Note / Reference</th><th class="px-5 py-3 text-right font-medium">Amount</th><th class="px-5 py-3 text-right font-medium">Actions</th></tr></thead><tbody class="divide-y"><tr v-for="row in rows" :key="row.id" class="hover:bg-slate-50"><td class="whitespace-nowrap px-5 py-4">{{ formatDate(row.received_date) }}</td><td class="max-w-md px-5 py-4"><p class="truncate" :class="row.note ? '' : 'text-slate-400'">{{ row.note || '—' }}</p></td><td class="whitespace-nowrap px-5 py-4 text-right font-semibold tabular-nums">{{ formatMoney(row.amount) }}</td><td class="px-5 py-4"><div class="flex justify-end gap-1"><button class="rounded p-2 text-slate-500 hover:bg-slate-100" aria-label="Edit received money" @click="startEdit(row)"><Pencil class="size-4" /></button><button class="rounded p-2 text-red-600 hover:bg-red-50" aria-label="Delete received money" @click="remove(row)"><Trash2 class="size-4" /></button></div></td></tr></tbody></table></div>
            <div class="divide-y md:hidden"><article v-for="row in rows" :key="row.id" class="p-4"><div class="flex items-start justify-between gap-3"><div class="min-w-0"><p class="text-sm font-medium">{{ formatDate(row.received_date) }}</p><p class="mt-1 truncate text-xs" :class="row.note ? 'text-slate-500' : 'text-slate-400'">{{ row.note || 'No note' }}</p></div><p class="whitespace-nowrap font-semibold">{{ formatMoney(row.amount) }}</p></div><div class="mt-3 flex justify-end gap-2"><button class="rounded p-2 text-slate-500" aria-label="Edit received money" @click="startEdit(row)"><Pencil class="size-4" /></button><button class="rounded p-2 text-red-600" aria-label="Delete received money" @click="remove(row)"><Trash2 class="size-4" /></button></div></article></div>
            <p v-if="!rows.length" class="p-8 text-center text-sm text-slate-500">No received money recorded yet.</p>
            <footer class="flex flex-col gap-3 border-t bg-slate-50 px-4 py-3 text-sm sm:flex-row sm:items-center sm:justify-between"><span>Showing {{ showingFrom }}–{{ showingTo }} of {{ meta.total }}</span><div class="flex flex-wrap gap-2"><BaseButton variant="secondary" :disabled="meta.current_page <= 1" @click="load(meta.current_page - 1)">Previous</BaseButton><BaseButton v-for="page in pageNumbers" :key="page" :variant="page === meta.current_page ? 'primary' : 'secondary'" @click="load(page)">{{ page }}</BaseButton><BaseButton variant="secondary" :disabled="meta.current_page >= meta.last_page" @click="load(meta.current_page + 1)">Next</BaseButton></div></footer>
        </section>
        <BaseDialog v-model:open="formOpen" :title="editing ? 'Edit received money' : 'Add received money'" description="Record money you received from Admin for office expenses." size="2xl">
            <p v-if="formError" class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ formError }}</p>
            <form class="mt-5 grid gap-4 sm:grid-cols-2" @submit.prevent="save">
                <label><span class="label">Date *</span><DatePicker v-model="form.received_date" aria-label="Received date" /><span v-if="formErrors.received_date" class="error-text">{{ formErrors.received_date[0] }}</span></label>
                <label><span class="label">Amount *</span><input v-model.number="form.amount" class="field" type="number" min="0.01" step="0.01" /><span v-if="formErrors.amount" class="error-text">{{ formErrors.amount[0] }}</span></label>
                <label class="sm:col-span-2"><span class="label">Note / Reference</span><textarea v-model="form.note" class="field min-h-24" maxlength="1000" /><span v-if="formErrors.note" class="error-text">{{ formErrors.note[0] }}</span></label>
                <div class="flex justify-end gap-2 border-t pt-4 sm:col-span-2"><BaseButton type="button" variant="secondary" @click="formOpen = false">Cancel</BaseButton><BaseButton type="submit" :loading="saving">{{ editing ? 'Save changes' : 'Add received money' }}</BaseButton></div>
            </form>
        </BaseDialog>
    </AppLayout>
</template>
