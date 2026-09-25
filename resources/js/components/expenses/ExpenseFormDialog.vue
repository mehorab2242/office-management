<script setup lang="ts">
import { ref, watch } from 'vue';
import BaseDialog from '../ui/BaseDialog.vue';
import LoadingState from '../ui/LoadingState.vue';
import ExpenseForm from './ExpenseForm.vue';
import { listCategories } from '../../services/categories';
import { createExpense, getExpense, updateExpense } from '../../services/expenses';
import { apiErrorMessage, validationErrors } from '../../services/api';
import { useToast } from '../../composables/useToast';
import type { Category, Expense, ExpensePayload, ValidationErrors } from '../../types';

const props = defineProps<{ open: boolean; expenseId: number | null }>();
const emit = defineEmits<{ 'update:open': [open: boolean]; saved: [] }>();
const toast = useToast();
const categories = ref<Category[]>([]);
const expense = ref<Expense | null>(null);
const loading = ref(false);
const saving = ref(false);
const error = ref('');
const errors = ref<ValidationErrors>({});

watch(() => [props.open, props.expenseId] as const, async ([open, expenseId]) => {
    if (!open) return;
    loading.value = true;
    error.value = '';
    errors.value = {};
    expense.value = null;
    try {
        const requests: [Promise<Category[]>, Promise<Expense> | null] = [listCategories(), expenseId ? getExpense(expenseId) : null];
        categories.value = await requests[0];
        if (requests[1]) expense.value = await requests[1];
    } catch (caught) { error.value = apiErrorMessage(caught, 'Unable to load the expense form.'); }
    finally { loading.value = false; }
}, { immediate: true });

async function submit(payload: ExpensePayload): Promise<void> {
    saving.value = true;
    errors.value = {};
    error.value = '';
    try {
        if (props.expenseId) { await updateExpense(props.expenseId, payload); toast.show('Expense updated'); }
        else { await createExpense(payload); toast.show('Expense created'); }
        emit('update:open', false);
        emit('saved');
    } catch (caught) { errors.value = validationErrors(caught); error.value = apiErrorMessage(caught); }
    finally { saving.value = false; }
}
</script>

<template>
    <BaseDialog :open="open" :title="expenseId ? 'Edit expense' : 'Add expense'" description="Record the date, category, and amount." size="2xl" @update:open="emit('update:open', $event)">
        <LoadingState v-if="loading" label="Loading expense form…" />
        <ExpenseForm v-else-if="!error" :expense="expense" :categories="categories" :loading="saving" :server-errors="errors" modal @submit="submit" @cancel="emit('update:open', false)" />
        <p v-else class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ error }}</p>
    </BaseDialog>
</template>
