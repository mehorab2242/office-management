<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AppLayout from '../components/layout/AppLayout.vue';
import ExpenseForm from '../components/expenses/ExpenseForm.vue';
import LoadingState from '../components/ui/LoadingState.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import { listCategories } from '../services/categories';
import { createExpense, getExpense, updateExpense } from '../services/expenses';
import { apiErrorMessage, validationErrors } from '../services/api';
import { useToast } from '../composables/useToast';
import type { Category, Expense, ExpensePayload, ValidationErrors } from '../types';

const route = useRoute(); const router = useRouter(); const toast = useToast();
const id = route.params.id ? Number(route.params.id) : null;
const expense = ref<Expense | null>(null); const categories = ref<Category[]>([]);
const loading = ref(true); const saving = ref(false); const error = ref(''); const errors = ref<ValidationErrors>({});

onMounted(async () => {
    try {
        const requests: [Promise<Category[]>, Promise<Expense> | null] = [listCategories(), id ? getExpense(id) : null];
        categories.value = await requests[0];
        if (requests[1]) expense.value = await requests[1];
    } catch (caught) { error.value = apiErrorMessage(caught, 'Unable to load the expense form.'); }
    finally { loading.value = false; }
});

async function submit(payload: ExpensePayload): Promise<void> {
    saving.value = true; errors.value = {};
    try {
        if (id) await updateExpense(id, payload); else await createExpense(payload);
        toast.show(id ? 'Expense updated' : 'Expense created');
        await router.push({ name: 'expenses' });
    } catch (caught) { errors.value = validationErrors(caught); error.value = apiErrorMessage(caught); }
    finally { saving.value = false; }
}
</script>
<template>
    <AppLayout><div class="mx-auto max-w-3xl"><div><p class="text-sm font-medium text-brand-700">Expenses</p><h1 class="mt-1 text-2xl font-semibold tracking-tight sm:text-3xl">{{ id ? 'Edit expense' : 'New expense' }}</h1><p class="mt-1 text-sm text-slate-500">{{ id ? 'Update the recorded details.' : 'Record a new office cost.' }}</p></div>
        <LoadingState v-if="loading" label="Loading form…" />
        <div v-else-if="error && (!categories.length || (id && !expense))" class="card mt-6 text-center"><p class="text-red-700">{{ error }}</p><RouterLink :to="{ name: 'expenses' }" class="mt-4 inline-block text-sm font-semibold text-brand-700">Back to expenses</RouterLink></div>
        <div v-else class="card mt-6"><p v-if="error" class="mb-5 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ error }}</p><ExpenseForm :expense="expense" :categories="categories" :loading="saving" :server-errors="errors" @submit="submit" /></div>
    </div></AppLayout>
</template>
