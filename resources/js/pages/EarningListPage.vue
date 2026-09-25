<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import AppLayout from '../components/layout/AppLayout.vue';
import BaseDialog from '../components/ui/BaseDialog.vue';
import DatePicker from '../components/ui/DatePicker.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import LoadingState from '../components/ui/LoadingState.vue';
import { createEarning, deleteEarning, listEarnings, updateEarning } from '../services/earnings';
import { apiErrorMessage, validationErrors } from '../services/api';
import { useToast } from '../composables/useToast';
import { formatDate, formatMoney } from '../utils/formatters';
import type { Earning, EarningPayload, PaginationMeta, ValidationErrors } from '../types';

const toast = useToast();
const rows = ref<Earning[]>([]);
const meta = ref<PaginationMeta>({ current_page: 1, last_page: 1, per_page: 15, total: 0 });
const loading = ref(true);
const saving = ref(false);
const error = ref('');
const formError = ref('');
const formErrors = ref<ValidationErrors>({});
const formOpen = ref(false);
const editing = ref<Earning | null>(null);
const filters = reactive({ search: '', source: '', date_from: '', date_to: '' });
const today = new Date();
const todayDate = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
const form = reactive<EarningPayload>({ earning_date: todayDate, source: '', description: '', amount: 0, reference: null, note: null });
const pageNumbers = computed(() => Array.from({ length: meta.value.last_page }, (_, index) => index + 1)
    .filter((page) => Math.abs(page - meta.value.current_page) <= 2));
const showingFrom = computed(() => meta.value.total === 0 ? 0 : (meta.value.current_page - 1) * meta.value.per_page + 1);
const showingTo = computed(() => Math.min(meta.value.current_page * meta.value.per_page, meta.value.total));

async function load(page = 1): Promise<void> {
    loading.value = true;
    try {
        const response = await listEarnings({ ...filters, page, per_page: meta.value.per_page });
        rows.value = response.data;
        meta.value = response.meta;
        error.value = '';
    }
    catch (caught) { error.value = apiErrorMessage(caught); }
    finally { loading.value = false; }
}

function startCreate(): void {
    editing.value = null;
    Object.assign(form, { earning_date: todayDate, source: '', description: '', amount: 0, reference: null, note: null });
    formErrors.value = {};
    formError.value = '';
    formOpen.value = true;
}

function startEdit(row: Earning): void {
    editing.value = row;
    Object.assign(form, { earning_date: row.earning_date, source: row.source, description: row.description, amount: Number(row.amount), reference: row.reference, note: row.note });
    formErrors.value = {};
    formError.value = '';
    formOpen.value = true;
}

async function save(): Promise<void> {
    saving.value = true;
    formErrors.value = {};
    formError.value = '';
    try {
        const payload = { ...form, source: form.source.trim(), description: form.description.trim(), reference: form.reference?.trim() || null, note: form.note?.trim() || null };
        if (editing.value) { await updateEarning(editing.value.id, payload); toast.show('Earning updated'); }
        else { await createEarning(payload); toast.show('Earning created'); }
        formOpen.value = false;
        await load(meta.value.current_page);
    } catch (caught) { formErrors.value = validationErrors(caught); formError.value = apiErrorMessage(caught); }
    finally { saving.value = false; }
}

async function remove(row: Earning): Promise<void> {
    if (!confirm(`Delete earning “${row.description}”?`)) return;
    try { await deleteEarning(row.id); toast.show('Earning deleted'); await load(meta.value.current_page); }
    catch (caught) { error.value = apiErrorMessage(caught); }
}

onMounted(load);
</script>

<template>
    <AppLayout>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="text-sm font-medium text-brand-700">Finance</p><h1 class="mt-1 text-3xl font-semibold">Earnings</h1><p class="mt-1 text-sm text-slate-500">Admin-only company revenue records.</p></div>
            <BaseButton @click="startCreate"><Plus class="size-4" />Add earning</BaseButton>
        </div>
        <form class="card mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4" @submit.prevent="load()">
            <label><span class="label">Search</span><input v-model="filters.search" class="field" placeholder="Search descriptions or references" /></label>
            <label><span class="label">Source</span><input v-model="filters.source" class="field" placeholder="Filter by earning source" /></label>
            <label><span class="label">From</span><DatePicker v-model="filters.date_from" placeholder="Start date" /></label>
            <label><span class="label">To</span><DatePicker v-model="filters.date_to" placeholder="End date" /></label>
            <div class="flex gap-2"><BaseButton type="submit">Filter</BaseButton><BaseButton type="button" variant="secondary" @click="Object.assign(filters, { search: '', source: '', date_from: '', date_to: '' }); load()">Clear</BaseButton></div>
        </form>
        <p v-if="error" class="mt-5 rounded-lg bg-red-50 p-3 text-red-700">{{ error }}</p>
        <LoadingState v-if="loading" label="Loading earnings…" />
        <section v-else class="mt-6 overflow-hidden rounded-xl border bg-white">
            <div class="hidden overflow-x-auto md:block"><table class="w-full min-w-[42rem] text-left text-sm"><thead class="border-b bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3 font-medium">Date</th><th class="px-5 py-3 font-medium">Description</th><th class="px-5 py-3 font-medium">Source</th><th class="px-5 py-3 text-right font-medium">Amount</th><th class="px-5 py-3 text-right font-medium">Actions</th></tr></thead><tbody class="divide-y"><tr v-for="row in rows" :key="row.id" class="hover:bg-slate-50"><td class="whitespace-nowrap px-5 py-4">{{ formatDate(row.earning_date) }}</td><td class="max-w-xs px-5 py-4"><p class="truncate font-medium">{{ row.description }}</p><p v-if="row.reference" class="mt-0.5 truncate text-xs text-slate-500">{{ row.reference }}</p></td><td class="px-5 py-4">{{ row.source }}</td><td class="whitespace-nowrap px-5 py-4 text-right font-semibold tabular-nums">{{ formatMoney(row.amount) }}</td><td class="px-5 py-4"><div class="flex justify-end gap-1"><button class="rounded p-2 text-slate-500 hover:bg-slate-100" aria-label="Edit earning" @click="startEdit(row)"><Pencil class="size-4" /></button><button class="rounded p-2 text-red-600 hover:bg-red-50" aria-label="Delete earning" @click="remove(row)"><Trash2 class="size-4" /></button></div></td></tr></tbody></table></div>
            <div class="divide-y md:hidden"><article v-for="row in rows" :key="row.id" class="p-4"><div class="flex items-start justify-between gap-3"><div class="min-w-0"><p class="truncate font-medium">{{ row.description }}</p><p class="mt-1 text-xs text-slate-500">{{ formatDate(row.earning_date) }} · {{ row.source }}</p><p v-if="row.reference" class="mt-1 truncate text-xs text-slate-500">{{ row.reference }}</p></div><p class="whitespace-nowrap font-semibold">{{ formatMoney(row.amount) }}</p></div><div class="mt-3 flex justify-end gap-2"><button class="rounded p-2 text-slate-500" aria-label="Edit earning" @click="startEdit(row)"><Pencil class="size-4" /></button><button class="rounded p-2 text-red-600" aria-label="Delete earning" @click="remove(row)"><Trash2 class="size-4" /></button></div></article></div>
            <p v-if="!rows.length" class="p-8 text-center text-sm text-slate-500">No earnings found.</p>
            <footer class="flex flex-col gap-3 border-t bg-slate-50 px-4 py-3 text-sm sm:flex-row sm:items-center sm:justify-between"><span>Showing {{ showingFrom }}–{{ showingTo }} of {{ meta.total }}</span><div class="flex flex-wrap gap-2"><BaseButton variant="secondary" :disabled="meta.current_page <= 1" @click="load(meta.current_page - 1)">Previous</BaseButton><BaseButton v-for="page in pageNumbers" :key="page" :variant="page === meta.current_page ? 'primary' : 'secondary'" @click="load(page)">{{ page }}</BaseButton><BaseButton variant="secondary" :disabled="meta.current_page >= meta.last_page" @click="load(meta.current_page + 1)">Next</BaseButton></div></footer>
        </section>
        <BaseDialog v-model:open="formOpen" :title="editing ? 'Edit earning' : 'Add earning'" description="Record company revenue." size="2xl">
            <p v-if="formError" class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ formError }}</p>
            <form class="mt-5 grid gap-4 sm:grid-cols-2" @submit.prevent="save">
                <label><span class="label">Date *</span><DatePicker v-model="form.earning_date" aria-label="Earning date" /><span v-if="formErrors.earning_date" class="error-text">{{ formErrors.earning_date[0] }}</span></label>
                <label><span class="label">Source / Category *</span><input v-model="form.source" class="field" placeholder="Client Payment" /><span v-if="formErrors.source" class="error-text">{{ formErrors.source[0] }}</span></label>
                <label class="sm:col-span-2"><span class="label">Description *</span><input v-model="form.description" class="field" /><span v-if="formErrors.description" class="error-text">{{ formErrors.description[0] }}</span></label>
                <label><span class="label">Amount *</span><input v-model.number="form.amount" class="field" type="number" min="0.01" step="0.01" /><span v-if="formErrors.amount" class="error-text">{{ formErrors.amount[0] }}</span></label>
                <label><span class="label">Reference</span><input v-model="form.reference" class="field" /><span v-if="formErrors.reference" class="error-text">{{ formErrors.reference[0] }}</span></label>
                <label class="sm:col-span-2"><span class="label">Note</span><textarea v-model="form.note" class="field min-h-24" /><span v-if="formErrors.note" class="error-text">{{ formErrors.note[0] }}</span></label>
                <div class="flex justify-end gap-2 border-t pt-4 sm:col-span-2"><BaseButton type="button" variant="secondary" @click="formOpen = false">Cancel</BaseButton><BaseButton type="submit" :loading="saving">{{ editing ? 'Save changes' : 'Add earning' }}</BaseButton></div>
            </form>
        </BaseDialog>
    </AppLayout>
</template>
