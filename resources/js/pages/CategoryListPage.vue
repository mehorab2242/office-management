<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import { Pencil, Plus, Tags, Trash2 } from '@lucide/vue';
import AppLayout from '../components/layout/AppLayout.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseDialog from '../components/ui/BaseDialog.vue';
import LoadingState from '../components/ui/LoadingState.vue';
import EmptyState from '../components/ui/EmptyState.vue';
import { createCategory, deleteCategory, listCategories, updateCategory } from '../services/categories';
import { apiErrorMessage, validationErrors } from '../services/api';
import { useToast } from '../composables/useToast';
import { useAuthStore } from '../stores/auth';
import type { Category, ValidationErrors } from '../types';

const auth = useAuthStore(); const toast = useToast(); const categories = ref<Category[]>([]); const loading = ref(true); const saving = ref(false); const error = ref('');
const formOpen = ref(false); const deleteOpen = ref(false); const selected = ref<Category | null>(null); const errors = ref<ValidationErrors>({});
const form = reactive({ name: '', description: '', is_active: true });

async function load(): Promise<void> { loading.value = true; error.value = ''; try { categories.value = await listCategories(); } catch (caught) { error.value = apiErrorMessage(caught); } finally { loading.value = false; } }
function startCreate(): void { selected.value = null; Object.assign(form, { name: '', description: '', is_active: true }); errors.value = {}; formOpen.value = true; }
function startEdit(category: Category): void { selected.value = category; Object.assign(form, { name: category.name, description: category.description ?? '', is_active: category.is_active }); errors.value = {}; formOpen.value = true; }
async function save(): Promise<void> {
    if (!form.name.trim()) { errors.value = { name: ['Enter a category name.'] }; return; }
    saving.value = true; errors.value = {};
    try {
        const payload = { name: form.name.trim(), description: form.description.trim() || null, is_active: form.is_active };
        if (selected.value) await updateCategory(selected.value.id, payload); else await createCategory(payload);
        formOpen.value = false; toast.show(selected.value ? 'Category updated' : 'Category created'); await load();
    } catch (caught) { errors.value = validationErrors(caught); error.value = apiErrorMessage(caught); }
    finally { saving.value = false; }
}
async function deactivate(): Promise<void> {
    if (!selected.value) return; saving.value = true;
    try { await deleteCategory(selected.value.id); deleteOpen.value = false; toast.show('Category deactivated'); await load(); }
    catch (caught) { toast.show('Unable to deactivate category', 'error', apiErrorMessage(caught)); }
    finally { saving.value = false; }
}
function confirmDeactivate(category: Category): void { selected.value = category; deleteOpen.value = true; }
onMounted(load);
</script>
<template>
    <AppLayout><div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-sm font-medium text-brand-700">Administration</p><h1 class="mt-1 text-2xl font-semibold tracking-tight sm:text-3xl">Categories</h1><p class="mt-1 text-sm text-slate-500">Organize expenses into consistent groups.</p></div><BaseButton v-if="auth.isSuperAdmin" @click="startCreate"><Plus class="size-4" />Add category</BaseButton></div>
        <p v-if="error" class="mt-6 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ error }}</p><LoadingState v-if="loading" label="Loading categories…" />
        <section v-else class="mt-6 overflow-hidden rounded-xl border bg-white"><EmptyState v-if="!categories.length" title="No categories yet" description="Create the first category to classify office expenses."><BaseButton v-if="auth.isSuperAdmin" @click="startCreate">Add category</BaseButton></EmptyState><div v-else class="divide-y"><article v-for="category in categories" :key="category.id" class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between"><div class="flex min-w-0 items-start gap-3"><span class="shrink-0 rounded-lg bg-brand-50 p-2 text-brand-700"><Tags class="size-5" /></span><div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><h2 class="break-words font-semibold">{{ category.name }}</h2><span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium" :class="category.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'">{{ category.is_active ? 'Active' : 'Inactive' }}</span></div><p class="mt-1 break-words text-sm text-slate-500">{{ category.description || 'No description' }}</p></div></div><div v-if="auth.isSuperAdmin" class="flex gap-2 self-end sm:shrink-0 sm:self-auto"><button class="rounded-lg border p-2 text-slate-600 hover:bg-slate-50" :aria-label="`Edit ${category.name}`" @click="startEdit(category)"><Pencil class="size-4" /></button><button v-if="category.is_active" class="rounded-lg border p-2 text-red-600 hover:bg-red-50" :aria-label="`Deactivate ${category.name}`" @click="confirmDeactivate(category)"><Trash2 class="size-4" /></button></div></article></div></section>
        <BaseDialog v-model:open="formOpen" :title="selected ? 'Edit category' : 'New category'" description="Categories help keep reports consistent."><form class="mt-6 space-y-5" @submit.prevent="save"><div><label class="label" for="category-name">Name *</label><input id="category-name" v-model="form.name" class="field" maxlength="255" /><p v-if="errors.name?.[0]" class="error-text">{{ errors.name[0] }}</p></div><div><label class="label" for="category-description">Description</label><textarea id="category-description" v-model="form.description" class="field min-h-24" maxlength="2000" /></div><label class="flex items-center gap-3 text-sm"><input v-model="form.is_active" type="checkbox" class="size-4 rounded border-slate-300 text-brand-600" />Active category</label><div class="flex justify-end gap-3"><BaseButton variant="secondary" @click="formOpen = false">Cancel</BaseButton><BaseButton type="submit" :loading="saving">{{ selected ? 'Save changes' : 'Create category' }}</BaseButton></div></form></BaseDialog>
        <BaseDialog v-model:open="deleteOpen" title="Deactivate category" description="Existing expenses keep their category; it will no longer be selectable for new entries."><p class="mt-5 text-sm text-slate-600">Deactivate <strong>{{ selected?.name }}</strong>?</p><div class="mt-6 flex justify-end gap-3"><BaseButton variant="secondary" @click="deleteOpen = false">Cancel</BaseButton><BaseButton variant="danger" :loading="saving" @click="deactivate">Deactivate</BaseButton></div></BaseDialog>
    </AppLayout>
</template>
